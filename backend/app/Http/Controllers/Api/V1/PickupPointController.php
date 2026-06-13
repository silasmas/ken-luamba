<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PickupPointResource;
use App\Models\PickupPoint;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Contrôleur API pour les points de retrait.
 */
class PickupPointController extends Controller
{
  /**
   * Liste les points de retrait actifs.
   *
   * @return AnonymousResourceCollection Collection de points de retrait
   */
  public function index(): AnonymousResourceCollection
  {
    $points = PickupPoint::query()
      ->where('is_active', true)
      ->orderBy('name')
      ->get();

    return PickupPointResource::collection($points);
  }
}
