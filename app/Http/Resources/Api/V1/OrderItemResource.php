<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API pour une ligne de commande.
 */
class OrderItemResource extends JsonResource
{
  /**
   * Transforme la ligne en tableau JSON.
   *
   * @param Request $request Requête HTTP entrante
   * @return array<string, mixed> Données sérialisées
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'bookTitle' => $this->book_title,
      'formatType' => $this->format_type->value,
      'formatLabel' => $this->format_type->label(),
      'quantity' => $this->quantity,
      'unitPrice' => (float) $this->unit_price,
      'totalPrice' => (float) $this->total_price,
    ];
  }
}
