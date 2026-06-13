<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\FulfillmentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Shipping\ShippingQuoteRequest;
use App\Services\ShippingService;
use Illuminate\Http\JsonResponse;

/**
 * Contrôleur API pour la configuration et le devis de livraison.
 */
class ShippingController extends Controller
{
  /**
   * Initialise le contrôleur avec le service livraison.
   *
   * @param ShippingService $shippingService Service de calcul des frais
   */
  public function __construct(
    private readonly ShippingService $shippingService,
  ) {}

  /**
   * Retourne la configuration publique des frais de livraison.
   *
   * @return JsonResponse Paramètres et zones
   */
  public function config(): JsonResponse
  {
    return response()->json([
      'data' => $this->shippingService->getPublicConfig(),
    ]);
  }

  /**
   * Calcule un devis de livraison pour une adresse.
   *
   * @param ShippingQuoteRequest $request Données validées
   * @return JsonResponse Montant et libellé
   */
  public function quote(ShippingQuoteRequest $request): JsonResponse
  {
    $fulfillmentType = FulfillmentType::from($request->validated('fulfillmentType'));

    $quote = $this->shippingService->quote(
      $fulfillmentType,
      $fulfillmentType === FulfillmentType::Delivery
        ? [
          'country' => $request->validated('country'),
          'city' => $request->validated('city'),
          'commune' => $request->validated('commune'),
        ]
        : null,
    );

    return response()->json([
      'data' => $quote,
    ]);
  }
}
