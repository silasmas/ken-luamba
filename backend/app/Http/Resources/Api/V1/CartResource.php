<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API pour un panier complet.
 */
class CartResource extends JsonResource
{
  /**
   * Résumé financier du panier.
   *
   * @var array<string, mixed>
   */
  public array $summary = [];

  /**
   * Attache le résumé calculé au panier.
   *
   * @param array<string, mixed> $summary Totaux et remises
   * @return $this Ressource enrichie
   */
  public function withSummary(array $summary): self
  {
    $this->summary = $summary;

    return $this;
  }

  /**
   * Transforme le panier en tableau JSON.
   *
   * @param Request $request Requête HTTP entrante
   * @return array<string, mixed> Données sérialisées
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'sessionId' => $this->session_id,
      'items' => CartItemResource::collection($this->whenLoaded('items')),
      'summary' => $this->summary,
    ];
  }
}
