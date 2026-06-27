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
    $settings = ShopSetting::instance();
    $currency = $settings->currency;

    return response()->json([
      'data' => [
        'currency' => $currency,
        'currencyLabel' => ShopCurrency::tryFrom($currency)?->label() ?? $currency,
        'extraContribution' => [
          'enabled' => (bool) $settings->extra_contribution_enabled,
          'label' => $settings->extra_contribution_label,
          'helpText' => $settings->extra_contribution_help_text,
        ],
      ],
    ]);
  }
}
