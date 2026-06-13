<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cart\AddCartItemRequest;
use App\Http\Requests\Api\V1\Cart\UpdateCartItemRequest;
use App\Http\Resources\Api\V1\CartResource;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur API pour la gestion du panier.
 */
class CartController extends Controller
{
  /**
   * Initialise le contrôleur panier.
   *
   * @param CartService $cartService Service métier panier
   */
  public function __construct(
    private readonly CartService $cartService,
  ) {}

  /**
   * Génère un identifiant de session pour panier invité.
   *
   * @return JsonResponse Identifiant de session
   */
  public function createSession(): JsonResponse
  {
    return response()->json([
      'sessionId' => $this->cartService->generateSessionId(),
    ]);
  }

  /**
   * Retourne le panier courant avec totaux.
   *
   * @param Request $request Requête HTTP
   * @return CartResource Panier et résumé
   */
  public function show(Request $request): CartResource
  {
    $cart = $this->cartService->resolveCart($request);
    $this->cartService->refreshCartPrices($cart);
    $cart->refresh()->load(['items.bookFormat.book.author', 'items.pricingPeriod']);
    $summary = $this->cartService->buildSummary($cart);

    return (new CartResource($cart))->withSummary($summary);
  }

  /**
   * Ajoute un article au panier.
   *
   * @param AddCartItemRequest $request Données validées
   * @return CartResource Panier mis à jour
   */
  public function storeItem(AddCartItemRequest $request): CartResource
  {
    $cart = $this->cartService->resolveCart($request);

    $this->cartService->addItem(
      $cart,
      $request->validated('bookFormatId'),
      $request->integer('quantity', 1),
    );

    $cart->refresh()->load(['items.bookFormat.book.author', 'items.pricingPeriod']);

    return (new CartResource($cart))->withSummary(
      $this->cartService->buildSummary($cart)
    );
  }

  /**
   * Met à jour la quantité d'une ligne panier.
   *
   * @param UpdateCartItemRequest $request Données validées
   * @param string $itemId Identifiant de la ligne
   * @return CartResource Panier mis à jour
   */
  public function updateItem(UpdateCartItemRequest $request, string $itemId): CartResource
  {
    $cart = $this->cartService->resolveCart($request);

    $this->cartService->updateItem(
      $cart,
      $itemId,
      $request->integer('quantity'),
    );

    $cart->refresh()->load(['items.bookFormat.book.author', 'items.pricingPeriod']);

    return (new CartResource($cart))->withSummary(
      $this->cartService->buildSummary($cart)
    );
  }

  /**
   * Supprime une ligne du panier.
   *
   * @param Request $request Requête HTTP
   * @param string $itemId Identifiant de la ligne
   * @return CartResource Panier mis à jour
   */
  public function destroyItem(Request $request, string $itemId): CartResource
  {
    $cart = $this->cartService->resolveCart($request);

    $this->cartService->removeItem($cart, $itemId);

    $cart->refresh()->load(['items.bookFormat.book.author', 'items.pricingPeriod']);

    return (new CartResource($cart))->withSummary(
      $this->cartService->buildSummary($cart)
    );
  }
}
