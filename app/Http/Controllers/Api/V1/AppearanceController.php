<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdminAppearanceSetting;
use Illuminate\Http\JsonResponse;

/**
 * Expose les couleurs de marque configurées dans l'admin au frontend client.
 */
class AppearanceController extends Controller
{
  /**
   * Retourne les couleurs publiques de l'interface boutique.
   *
   * @return JsonResponse Palette (primaire, focus champs, texte bouton)
   */
  public function __invoke(): JsonResponse
  {
    $settings = AdminAppearanceSetting::instance();

    return response()->json([
      'data' => [
        'colorPrimary' => $settings->color_primary,
        'colorInputFocus' => $settings->color_input_focus,
        'colorButtonText' => $settings->color_button_text,
      ],
    ]);
  }
}
