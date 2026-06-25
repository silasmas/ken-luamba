<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API pour une ligne de panier.
 */
class CartItemResource extends JsonResource
{
  /**
   * Transforme la ligne panier en tableau JSON.
   *
   * @param Request $request Requête HTTP entrante
   * @return array<string, mixed> Données sérialisées
   */
  public function toArray(Request $request): array
  {
    $book = $this->bookFormat?->book;

    return [
      'id' => $this->id,
      'quantity' => $this->quantity,
      'unitPrice' => $this->unit_price,
      'lineTotal' => round($this->lineTotal(), 2),
      'book' => [
        'id' => $book?->id,
        'title' => $book?->title,
        'slug' => $book?->slug,
        'coverImage' => \App\Support\MediaUrl::fromPath($book?->cover_image),
        'authorName' => $book?->author?->full_name,
      ],
      'format' => [
        'id' => $this->bookFormat?->id,
        'type' => $this->bookFormat?->type->value,
        'typeLabel' => $this->bookFormat?->type->label(),
        'sku' => $this->bookFormat?->sku,
        'isDigital' => $this->bookFormat?->type->isDigital(),
        'stockQuantity' => $this->bookFormat?->type->isDigital()
          ? null
          : $this->bookFormat?->stock_quantity,
        'maxQuantity' => $this->bookFormat?->maxOrderQuantity(),
      ],
      'pricingPeriod' => [
        'id' => $this->pricingPeriod?->id,
        'label' => $this->pricingPeriod?->label,
        'type' => $this->pricingPeriod?->type->value,
      ],
    ];
  }
}
