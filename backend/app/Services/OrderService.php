<?php

namespace App\Services;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Models\BookFormat;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use App\Enums\PaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Service de création de commandes depuis le panier.
 */
class OrderService
{
  /**
   * Initialise le service commande.
   *
   * @param CartService $cartService Service panier
   * @param DiscountService $discountService Service remises
   */
  public function __construct(
    private readonly CartService $cartService,
    private readonly DiscountService $discountService,
    private readonly ShippingService $shippingService,
  ) {}

  /**
   * Crée une commande à partir du panier courant.
   *
   * @param Request $request Requête HTTP (session panier)
   * @param User $user Client connecté
   * @param array<string, mixed> $payload Données checkout validées
   * @return Order Commande créée
   */
  public function createFromCart(Request $request, User $user, array $payload): Order
  {
    $cart = $this->cartService->resolveCart($request);
    $cart->load(['items.bookFormat.book', 'items.pricingPeriod']);

    if ($cart->items->isEmpty()) {
      throw ValidationException::withMessages([
        'cart' => ['Le panier est vide.'],
      ]);
    }

    $summary = $this->cartService->buildSummary($cart);
    $hasPhysical = $cart->items->contains(
      fn ($item) => ! $item->bookFormat->type->isDigital()
    );

    $fulfillmentType = isset($payload['fulfillmentType'])
      ? FulfillmentType::from($payload['fulfillmentType'])
      : null;

    if ($hasPhysical && $fulfillmentType === null) {
      throw ValidationException::withMessages([
        'fulfillmentType' => ['Choisissez livraison ou retrait.'],
      ]);
    }

    if ($fulfillmentType === FulfillmentType::Delivery && empty($payload['shippingAddress'])) {
      throw ValidationException::withMessages([
        'shippingAddress' => ['Adresse de livraison requise.'],
      ]);
    }

    if ($fulfillmentType === FulfillmentType::Pickup && empty($payload['pickupPointId'])) {
      throw ValidationException::withMessages([
        'pickupPointId' => ['Point de retrait requis.'],
      ]);
    }

    $shippingAmount = 0;
    $shippingMeta = [];

    if ($fulfillmentType === FulfillmentType::Delivery) {
      $quote = $this->shippingService->quote($fulfillmentType, $payload['shippingAddress'] ?? null);
      $shippingAmount = (float) $quote['amount'];
      $shippingMeta = [
        'zoneId' => $quote['zoneId'] ?? null,
        'zoneName' => $quote['zoneName'] ?? null,
        'shippingLabel' => $quote['label'] ?? null,
        'requiresQuote' => $quote['requiresQuote'] ?? false,
        'isInternational' => $quote['isInternational'] ?? false,
      ];

      if (($quote['requiresQuote'] ?? false) === true) {
        $payload['notes'] = trim(
          (($payload['notes'] ?? '').' Livraison internationale — frais de fret sur devis.')
        );
      }
    }

    $shippingAddress = $payload['shippingAddress'] ?? null;

    if (is_array($shippingAddress)) {
      $shippingAddress = array_merge($shippingAddress, array_filter($shippingMeta));
    }

    return DB::transaction(function () use (
      $user,
      $cart,
      $summary,
      $payload,
      $fulfillmentType,
      $shippingAmount,
      $shippingAddress,
    ): Order {
      $order = Order::query()->create([
        'order_number' => $this->generateOrderNumber(),
        'user_id' => $user->id,
        'status' => OrderStatus::PendingPayment,
        'fulfillment_type' => $fulfillmentType,
        'pickup_point_id' => $payload['pickupPointId'] ?? null,
        'shipping_address' => $shippingAddress,
        'subtotal' => $summary['subtotal'],
        'discount_amount' => $summary['discount']['amount'],
        'shipping_amount' => $shippingAmount,
        'total' => $summary['total'] + $shippingAmount,
        'currency' => $summary['currency'],
        'notes' => $payload['notes'] ?? null,
      ]);

      foreach ($cart->items as $item) {
        OrderItem::query()->create([
          'order_id' => $order->id,
          'book_format_id' => $item->book_format_id,
          'book_title' => $item->bookFormat->book->title,
          'format_type' => $item->bookFormat->type,
          'quantity' => $item->quantity,
          'unit_price' => $item->unit_price,
          'total_price' => $item->lineTotal(),
          'pricing_period_id' => $item->pricing_period_id,
        ]);
      }

      Payment::query()->create([
        'order_id' => $order->id,
        'amount' => $order->total,
        'currency' => $order->currency,
        'status' => PaymentStatus::Pending,
      ]);

      return $order->load(['items', 'payment', 'pickupPoint']);
    });
  }

  /**
   * Génère un numéro de commande unique.
   *
   * @return string Numéro formaté KL-YYYY-XXXXX
   */
  private function generateOrderNumber(): string
  {
    do {
      $number = 'KL-'.now()->format('Y').'-'.strtoupper(Str::random(6));
    } while (Order::query()->where('order_number', $number)->exists());

    return $number;
  }
}
