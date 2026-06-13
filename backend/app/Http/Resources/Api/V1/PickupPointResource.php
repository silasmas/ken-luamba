<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API pour un point de retrait.
 */
class PickupPointResource extends JsonResource
{
  /**
   * Transforme le point de retrait en tableau JSON.
   *
   * @param Request $request Requête HTTP entrante
   * @return array<string, mixed> Données sérialisées
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'address' => $this->address,
      'city' => $this->city,
      'phone' => $this->phone,
      'openingHours' => $this->opening_hours,
    ];
  }
}
