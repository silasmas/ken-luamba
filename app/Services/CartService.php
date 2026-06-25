<?php

namespace App\Services;

use App\Models\BookFormat;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ShopSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Service de gestion du panier (invité et connecté).
 */
class CartService
{
  /**
   * Initialise le service panier.
   *
   * @param PricingService $pricingService Service de tarification
   * @param DiscountService $discountService Service de remises
   */
  public function __construct(
    private readonly PricingService $pricingService,
    private readonly DiscountService $discountService,
  ) {}

  /**
   * Résout ou crée le panier depuis la requête HTTP.
   *
   * @param Request $request Requête avec token ou session
   * @return Cart Panier actif
   */
  public function resolveCart(Request $request): Cart
  {
    $user = $request->user();

    if ($user instanceof User) {
      return $this->resolveUserCart($user, $request->header('X-Cart-Session'));
    }

    $sessionId = $request->header('X-Cart-Session');

    if ($sessionId === null || $sessionId === '') {
      throw ValidationException::withMessages([
        'session' => ['Identifiant de session panier requis (X-Cart-Session).'],
      ]);
    }

    return Cart::query()->firstOrCreate(
      ['session_id' => $sessionId],
      ['expires_at' => now()->addDays(30)],
    );
  }

  /**
   * Fusionne le panier invité dans le panier utilisateur après connexion.
   *
   * @param User $user Utilisateur connecté
   * @param string|null $sessionId Session panier invité
   * @return void
   */
  public function mergeGuestCart(User $user, ?string $sessionId): void
  {
    if ($sessionId === null || $sessionId === '') {
      return;
    }

    $guestCart = Cart::query()->where('session_id', $sessionId)->with('items')->first();

    if ($guestCart === null || $guestCart->items->isEmpty()) {
      return;
    }

    $userCart = $this->resolveUserCart($user, null);
    $userCart->loadMissing('items.pricingPeriod');
    $userCurrency = $userCart->items->first()?->pricingPeriod?->currency;

    foreach ($guestCart->items as $guestItem) {
      $guestItem->loadMissing('pricingPeriod');
      $guestCurrency = $guestItem->pricingPeriod?->currency;

      if ($userCurrency !== null && $guestCurrency !== null && $userCurrency !== $guestCurrency) {
        continue;
      }

      $existing = $userCart->items()
        ->where('book_format_id', $guestItem->book_format_id)
        ->first();

      if ($existing !== null) {
        $existing->update([
          'quantity' => $existing->quantity + $guestItem->quantity,
        ]);
      } else {
        $guestItem->update(['cart_id' => $userCart->id]);
      }
    }

    $guestCart->delete();
  }

  /**
   * Ajoute ou incrémente un article dans le panier.
   *
   * @param Cart $cart Panier cible
   * @param string $bookFormatId Identifiant du format
   * @param int $quantity Quantité à ajouter
   * @return CartItem Ligne mise à jour
   */
  public function addItem(Cart $cart, string $bookFormatId, int $quantity = 1): CartItem
  {
    $format = BookFormat::query()
      ->active()
      ->with(['book', 'pricingPeriods'])
      ->find($bookFormatId);

    if ($format === null || ! $format->book?->is_published) {
      throw ValidationException::withMessages([
        'bookFormatId' => ['Format de livre indisponible.'],
      ]);
    }

    $period = $this->pricingService->getCurrentPeriod($format);

    if ($period === null) {
      throw ValidationException::withMessages([
        'bookFormatId' => ['Aucun tarif actif pour ce format.'],
      ]);
    }

    $cart->loadMissing('items.pricingPeriod');
    $cartCurrency = $cart->items->first()?->pricingPeriod?->currency;

    if ($cartCurrency !== null && $cartCurrency !== $period->currency) {
      throw ValidationException::withMessages([
        'bookFormatId' => [
          'Impossible de mélanger '.$cartCurrency.' et '.$period->currency.' dans le même panier. Videz le panier ou achetez en une seule devise.',
        ],
      ]);
    }

    $item = $cart->items()->where('book_format_id', $format->id)->first();

    if ($item !== null) {
      $newQuantity = $item->quantity + $quantity;
      $this->assertQuantityWithinStock($format, $newQuantity);

      $item->update([
        'quantity' => $newQuantity,
        'unit_price' => $period->price,
        'pricing_period_id' => $period->id,
      ]);

      return $item->fresh(['bookFormat.book', 'pricingPeriod']);
    }

    $this->assertQuantityWithinStock($format, $quantity);

    return $cart->items()->create([
      'book_format_id' => $format->id,
      'quantity' => $quantity,
      'unit_price' => $period->price,
      'pricing_period_id' => $period->id,
    ])->load(['bookFormat.book', 'pricingPeriod']);
  }

  /**
   * Met à jour la quantité d'une ligne panier.
   *
   * @param Cart $cart Panier parent
   * @param string $itemId Identifiant de la ligne
   * @param int $quantity Nouvelle quantité
   * @return CartItem Ligne mise à jour
   */
  public function updateItem(Cart $cart, string $itemId, int $quantity): CartItem
  {
    $item = $this->findCartItem($cart, $itemId);
    $item->loadMissing('bookFormat.book');

    if ($quantity <= 0) {
      $item->delete();

      throw ValidationException::withMessages([
        'quantity' => ['Quantité invalide. Utilisez la suppression pour retirer un article.'],
      ]);
    }

    $this->assertQuantityWithinStock($item->bookFormat, $quantity);

    $period = $this->pricingService->getCurrentPeriod($item->bookFormat);

    $item->update([
      'quantity' => $quantity,
      'unit_price' => $period?->price ?? $item->unit_price,
      'pricing_period_id' => $period?->id ?? $item->pricing_period_id,
    ]);

    return $item->fresh(['bookFormat.book', 'pricingPeriod']);
  }

  /**
   * Supprime une ligne du panier.
   *
   * @param Cart $cart Panier parent
   * @param string $itemId Identifiant de la ligne
   * @return void
   */
  public function removeItem(Cart $cart, string $itemId): void
  {
    $this->findCartItem($cart, $itemId)->delete();
  }

  /**
   * Met à jour les prix enregistrés selon les tarifs actifs.
   *
   * @param Cart $cart Panier à synchroniser
   * @return void
   */
  public function refreshCartPrices(Cart $cart): void
  {
    $cart->loadMissing(['items.bookFormat.pricingPeriods']);

    foreach ($cart->items as $item) {
      $currentPeriod = $this->pricingService->getCurrentPeriod($item->bookFormat);

      if ($currentPeriod === null) {
        continue;
      }

      $priceChanged = (float) $currentPeriod->price !== (float) $item->unit_price;
      $periodChanged = $item->pricing_period_id !== $currentPeriod->id;

      if ($priceChanged || $periodChanged) {
        $item->update([
          'unit_price' => $currentPeriod->price,
          'pricing_period_id' => $currentPeriod->id,
        ]);
      }
    }
  }

  /**
   * Calcule les totaux et alertes du panier.
   *
   * @param Cart $cart Panier à calculer
   * @return array<string, mixed> Résumé financier
   */
  public function buildSummary(Cart $cart): array
  {
    $cart->load([
      'items.bookFormat.book.author',
      'items.pricingPeriod',
    ]);

    $subtotal = $cart->items->sum(fn (CartItem $item): float => $item->lineTotal());
    $discount = $this->discountService->calculate($cart, $subtotal);
    $total = max($subtotal - $discount['amount'], 0);

    $priceAlerts = $cart->items
      ->map(function (CartItem $item): ?array {
        $currentPeriod = $this->pricingService->getCurrentPeriod($item->bookFormat);

        if ($currentPeriod === null) {
          return [
            'itemId' => $item->id,
            'message' => 'Ce format n\'est plus disponible à la vente.',
          ];
        }

        if ((float) $currentPeriod->price !== (float) $item->unit_price) {
          return [
            'itemId' => $item->id,
            'message' => 'Le prix a changé depuis l\'ajout au panier.',
            'oldPrice' => $item->unit_price,
            'newPrice' => $currentPeriod->price,
          ];
        }

        return null;
      })
      ->filter()
      ->values()
      ->all();

    return [
      'itemCount' => $cart->items->sum('quantity'),
      'subtotal' => round($subtotal, 2),
      'discount' => $discount,
      'total' => round($total, 2),
      'currency' => ShopSetting::currencyCode(),
      'priceAlerts' => $priceAlerts,
    ];
  }

  /**
   * Vide le panier d'un utilisateur après paiement réussi.
   *
   * @param int $userId Identifiant utilisateur
   * @return void
   */
  public function clearUserCart(int $userId): void
  {
    $cart = Cart::query()->where('user_id', $userId)->first();

    if ($cart !== null) {
      $cart->items()->delete();
    }
  }

  /**
   * Génère un identifiant de session panier invité.
   *
   * @return string UUID de session
   */
  public function generateSessionId(): string
  {
    return (string) Str::uuid();
  }

  /**
   * Résout le panier d'un utilisateur connecté.
   *
   * @param User $user Utilisateur connecté
   * @param string|null $sessionId Session invité à fusionner
   * @return Cart Panier utilisateur
   */
  private function resolveUserCart(User $user, ?string $sessionId): Cart
  {
    $this->mergeGuestCart($user, $sessionId);

    return Cart::query()->firstOrCreate(
      ['user_id' => $user->id],
      ['expires_at' => now()->addDays(30)],
    );
  }

  /**
   * Trouve une ligne panier appartenant au panier donné.
   *
   * @param Cart $cart Panier parent
   * @param string $itemId Identifiant de ligne
   * @return CartItem Ligne trouvée
   */
  private function findCartItem(Cart $cart, string $itemId): CartItem
  {
    $item = $cart->items()->where('id', $itemId)->first();

    if ($item === null) {
      throw ValidationException::withMessages([
        'itemId' => ['Article introuvable dans le panier.'],
      ]);
    }

    return $item;
  }

  /**
   * Vérifie que la quantité demandée ne dépasse pas le stock du format.
   *
   * @param BookFormat $format Format concerné
   * @param int $quantity Quantité souhaitée
   * @return void
   */
  private function assertQuantityWithinStock(BookFormat $format, int $quantity): void
  {
    $format->loadMissing('book');
    $max = $format->maxOrderQuantity();
    $bookTitle = $format->book?->title ?? 'ce livre';
    $formatLabel = $format->type->label();

    if ($format->type->isDigital()) {
      if ($quantity > $max) {
        throw ValidationException::withMessages([
          'quantity' => ["La quantité maximale pour ce format numérique est de {$max}."],
        ]);
      }

      return;
    }

    if ($max <= 0) {
      throw ValidationException::withMessages([
        'quantity' => ["« {$bookTitle} » ({$formatLabel}) est momentanément en rupture de stock."],
      ]);
    }

    if ($quantity > $max) {
      $stockLabel = $max === 1
        ? '1 exemplaire disponible'
        : "{$max} exemplaires disponibles";

      throw ValidationException::withMessages([
        'quantity' => ["Stock insuffisant pour « {$bookTitle} » ({$formatLabel}) : {$stockLabel}."],
      ]);
    }
  }
}
