<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ShopCurrency;
use App\Http\Controllers\Controller;
use App\Models\ShopSetting;
use Illuminate\Http\JsonResponse;

/**
 * Expose la configuration publique de la boutique (devises, etc.).
 */
class ShopConfigController extends Controller
{
  /**
   * Retourne la devise configurée pour la boutique.
   *
   * @return JsonResponse Devise active (CDF ou USD)
   */
  public function __invoke(): JsonResponse
  {
    $currency = ShopSetting::currencyCode();

    return response()->json([
      'data' => [
        'currency' => $currency,
        'currencyLabel' => ShopCurrency::tryFrom($currency)?->label() ?? $currency,
      ],
    ]);
  }
}
