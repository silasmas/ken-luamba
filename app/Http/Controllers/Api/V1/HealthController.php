<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Contrôleur de santé de l'API.
 * Vérifie que le backend Laravel répond correctement.
 */
class HealthController extends Controller
{
  /**
   * Retourne l'état de l'API et les métadonnées du projet.
   *
   * @return JsonResponse Réponse JSON avec le statut et la version
   */
  public function __invoke(): JsonResponse
  {
    return response()->json([
      'status' => 'ok',
      'service' => 'ken-luamba-api',
      'version' => 'v1',
    ]);
  }
}
