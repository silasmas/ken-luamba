<?php

namespace App\Services;

use App\Enums\BookFormatType;
use App\Enums\FulfillmentType;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\BookFormat;
use App\Models\Cart;
use App\Models\DirectPaymentSetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ShopSetting;
use App\Models\User;
use App\Support\MediaUrl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Orchestration du parcours public « paiement direct » (pack + email + FlexPay).
 */
class DirectPaymentService
{
  /**
   * Initialise le service paiement direct.
   *
   * @param CartService $cartService Service panier (pricing temporaire)
   * @param PaymentService $paymentService Orchestration FlexPay
   */
  public function __construct(
    private readonly CartService $cartService,
    private readonly PaymentService $paymentService,
  ) {}

  /**
   * Construit le catalogue public avec pack sélectionné par défaut.
   *
   * @return array<string, mixed> Catalogue + totaux estimés pour le pack
   */
  public function catalog(): array
  {
    $settings = DirectPaymentSetting::instance();

    if (! $settings->is_enabled) {
      throw ValidationException::withMessages([
        'directPayment' => ['Le paiement direct est temporairement indisponible.'],
      ]);
    }

    $formats = $this->availablePhysicalFormats();
    $defaultSelectedIds = $this->resolveDefaultPackFormatIds($settings, $formats);

    $quote = $this->quoteTotals($defaultSelectedIds);

    return [
      'enabled' => true,
      'title' => $settings->title,
      'message' => $settings->message,
      'publicUrl' => $settings->publicUrl(),
      'defaultSelectedIds' => $defaultSelectedIds,
      'books' => $formats->map(function (BookFormat $format) use ($defaultSelectedIds): array {
        $book = $format->book;
        $period = $format->relationLoaded('pricingPeriods')
          ? app(PricingService::class)->getCurrentPeriod($format)
          : null;

        return [
          'bookId' => $book?->id,
          'bookFormatId' => $format->id,
          'title' => $book?->title,
          'slug' => $book?->slug,
          'coverImage' => MediaUrl::fromPath($book?->cover_image),
          'formatType' => $format->type->value,
          'formatLabel' => $format->type->label(),
          'price' => $period !== null ? (float) $period->price : null,
          'currency' => $period?->currency ?? ShopSetting::currencyCode(),
          'selectedByDefault' => in_array($format->id, $defaultSelectedIds, true),
        ];
      })->values()->all(),
      'packQuote' => $quote,
    ];
  }

  /**
   * Crée une commande guest et initie le paiement FlexPay.
   *
   * @param array<string, mixed> $payload Email, formats, canal de paiement
   * @return array<string, mixed> Résultat checkout + initiation paiement
   */
  public function checkout(array $payload): array
  {
    $settings = DirectPaymentSetting::instance();

    if (! $settings->is_enabled) {
      throw ValidationException::withMessages([
        'directPayment' => ['Le paiement direct est temporairement indisponible.'],
      ]);
    }

    $email = Str::lower(trim((string) ($payload['email'] ?? '')));
    $bookFormatIds = array_values(array_unique($payload['bookFormatIds'] ?? []));
    $channel = PaymentChannel::from((string) $payload['channel']);

    if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
      throw ValidationException::withMessages([
        'email' => ['Adresse email invalide.'],
      ]);
    }

    if ($bookFormatIds === []) {
      throw ValidationException::withMessages([
        'bookFormatIds' => ['Sélectionnez au moins un livre.'],
      ]);
    }

    $this->assertPhysicalFormats($bookFormatIds);

    $user = $this->resolveGuestUser($email);

    $order = DB::transaction(function () use ($user, $bookFormatIds): Order {
      $cart = Cart::query()->create([
        'session_id' => 'direct-payment-'.Str::uuid(),
        'expires_at' => now()->addHour(),
      ]);

      try {
        foreach ($bookFormatIds as $formatId) {
          $this->cartService->addItem($cart, $formatId, 1);
        }

        $cart->refresh()->load(['items.bookFormat.book', 'items.pricingPeriod']);
        $summary = $this->cartService->buildSummary($cart);

        if (count($summary['priceAlerts']) > 0) {
          throw ValidationException::withMessages([
            'bookFormatIds' => ['Certains livres ne sont plus disponibles à la vente.'],
          ]);
        }

        $order = Order::query()->create([
          'order_number' => $this->generateOrderNumber(),
          'source' => OrderSource::DirectPayment,
          'public_token' => $this->generatePublicToken(),
          'user_id' => $user->id,
          'status' => OrderStatus::PendingPayment,
          'fulfillment_type' => FulfillmentType::Pickup,
          'pickup_point_id' => null,
          'shipping_address' => null,
          'subtotal' => $summary['subtotal'],
          'discount_amount' => $summary['discount']['amount'],
          'shipping_amount' => 0,
          'extra_contribution_amount' => 0,
          'total' => $summary['total'],
          'currency' => $summary['currency'],
          'notes' => 'Paiement direct — remise en main propre',
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

        return $order->load(['items', 'payment', 'user']);
      } finally {
        $cart->items()->delete();
        $cart->delete();
      }
    });

    $paymentResult = $this->paymentService->initiate(
      $order,
      $channel,
      $payload['phone'] ?? null,
      $payload['providerCode'] ?? null,
    );

    return [
      'orderNumber' => $order->order_number,
      'publicToken' => $order->public_token,
      'resultUrl' => $this->resultUrl($order->public_token),
      'total' => (float) $order->total,
      'currency' => $order->currency,
      'payment' => $paymentResult,
    ];
  }

  /**
   * Retourne l'état public d'une commande paiement direct.
   *
   * @param string $publicToken Token public de la commande
   * @return array<string, mixed> Statut, articles, QR
   */
  public function result(string $publicToken): array
  {
    $order = Order::query()
      ->where('public_token', $publicToken)
      ->where('source', OrderSource::DirectPayment)
      ->with(['items.bookFormat.book', 'payment', 'qrCode', 'user', 'delivery'])
      ->first();

    if ($order === null) {
      throw ValidationException::withMessages([
        'token' => ['Commande introuvable.'],
      ]);
    }

    $payment = $order->payment;
    $qrToken = $order->qrCode?->token;
    $frontendUrl = rtrim((string) config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3001')), '/');

    return [
      'orderNumber' => $order->order_number,
      'publicToken' => $order->public_token,
      'email' => $order->user?->email,
      'status' => $order->status->value,
      'statusLabel' => $order->status->label(),
      'paymentStatus' => $payment?->status?->value,
      'paymentChannel' => $payment?->channel?->value,
      'isPaid' => $payment?->status === PaymentStatus::Completed,
      'total' => (float) $order->total,
      'currency' => $order->currency,
      'subtotal' => (float) $order->subtotal,
      'discountAmount' => (float) $order->discount_amount,
      'items' => $order->items->map(fn (OrderItem $item): array => [
        'bookTitle' => $item->book_title,
        'formatLabel' => $item->format_type->label(),
        'quantity' => $item->quantity,
        'unitPrice' => (float) $item->unit_price,
        'coverImage' => MediaUrl::fromPath($item->bookFormat?->book?->cover_image),
      ])->values()->all(),
      'qrToken' => $qrToken,
      'scanUrl' => $qrToken !== null ? $frontendUrl.'/livreur/scan/'.$qrToken : null,
      'qrUsed' => (bool) ($order->qrCode?->is_used),
      'deliveryStatus' => $order->delivery?->status?->value,
    ];
  }

  /**
   * Calcule les totaux pour une sélection de formats.
   *
   * @param list<string> $bookFormatIds Identifiants de formats
   * @return array<string, mixed> Totaux estimés
   */
  public function quoteTotals(array $bookFormatIds): array
  {
    if ($bookFormatIds === []) {
      return [
        'subtotal' => 0.0,
        'discountAmount' => 0.0,
        'total' => 0.0,
        'currency' => ShopSetting::currencyCode(),
        'discount' => null,
      ];
    }

    $cart = Cart::query()->create([
      'session_id' => 'direct-payment-quote-'.Str::uuid(),
      'expires_at' => now()->addMinutes(10),
    ]);

    try {
      foreach ($bookFormatIds as $formatId) {
        $this->cartService->addItem($cart, $formatId, 1);
      }

      $summary = $this->cartService->buildSummary($cart);

      return [
        'subtotal' => $summary['subtotal'],
        'discountAmount' => $summary['discount']['amount'],
        'total' => $summary['total'],
        'currency' => $summary['currency'],
        'discount' => $summary['discount']['rule'],
      ];
    } finally {
      $cart->items()->delete();
      $cart->delete();
    }
  }

  /**
   * Liste les formats physiques publiés et tarifés.
   *
   * @return \Illuminate\Support\Collection<int, BookFormat>
   */
  private function availablePhysicalFormats()
  {
    return BookFormat::query()
      ->active()
      ->whereIn('type', [
        BookFormatType::Hardcover->value,
        BookFormatType::Paperback->value,
      ])
      ->whereHas('book', fn ($query) => $query->published())
      ->with(['book', 'pricingPeriods'])
      ->get()
      ->filter(fn (BookFormat $format): bool => app(PricingService::class)->getCurrentPeriod($format) !== null)
      ->values();
  }

  /**
   * Résout les IDs du pack par défaut (settings ou tous les formats physiques).
   *
   * @param DirectPaymentSetting $settings Paramètres module
   * @param \Illuminate\Support\Collection<int, BookFormat> $formats Formats disponibles
   * @return list<string> Identifiants sélectionnés
   */
  private function resolveDefaultPackFormatIds(DirectPaymentSetting $settings, $formats): array
  {
    $configured = array_values(array_filter($settings->pack_book_format_ids ?? []));
    $availableIds = $formats->pluck('id')->all();

    if ($configured !== []) {
      return array_values(array_intersect($configured, $availableIds));
    }

    // Un format physique par livre (le premier) pour composer le pack.
    return $formats
      ->groupBy('book_id')
      ->map(fn ($group) => $group->first()->id)
      ->values()
      ->all();
  }

  /**
   * Vérifie que les formats sont physiques, actifs et tarifés.
   *
   * @param list<string> $bookFormatIds Identifiants demandés
   */
  private function assertPhysicalFormats(array $bookFormatIds): void
  {
    $formats = BookFormat::query()
      ->active()
      ->whereIn('id', $bookFormatIds)
      ->with('book')
      ->get();

    if ($formats->count() !== count($bookFormatIds)) {
      throw ValidationException::withMessages([
        'bookFormatIds' => ['Un ou plusieurs livres sont introuvables.'],
      ]);
    }

    foreach ($formats as $format) {
      if ($format->type->isDigital() || ! $format->book?->is_published) {
        throw ValidationException::withMessages([
          'bookFormatIds' => ['Seuls les livres physiques publiés sont acceptés.'],
        ]);
      }
    }
  }

  /**
   * Crée ou retrouve un client guest par email.
   *
   * @param string $email Email normalisé
   * @return User Client associé
   */
  private function resolveGuestUser(string $email): User
  {
    $localPart = Str::limit(Str::before($email, '@'), 80, '');

    return User::query()->firstOrCreate(
      ['email' => $email],
      [
        'name' => $localPart !== '' ? $localPart : 'client',
        'full_name' => $localPart !== '' ? $localPart : 'Client',
        'password' => Hash::make(Str::random(40)),
        'role' => UserRole::Client,
        'is_active' => true,
        'email_verified_at' => now(),
      ],
    );
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

  /**
   * Génère un token public unique pour la page résultat.
   *
   * @return string Token opaque
   */
  private function generatePublicToken(): string
  {
    do {
      $token = Str::random(48);
    } while (Order::query()->where('public_token', $token)->exists());

    return $token;
  }

  /**
   * Construit l'URL frontend de la page résultat.
   *
   * @param string $publicToken Token public
   * @return string URL absolue
   */
  private function resultUrl(string $publicToken): string
  {
    $frontendUrl = rtrim((string) config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3001')), '/');

    return $frontendUrl.'/paiement-direct/result/'.$publicToken;
  }
}
