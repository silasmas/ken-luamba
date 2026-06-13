<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API pour un témoignage lecteur approuvé.
 */
class BookReviewResource extends JsonResource
{
  /**
   * Transforme le témoignage en tableau JSON public.
   *
   * @param Request $request Requête HTTP entrante
   * @return array<string, mixed> Données sérialisées
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'authorName' => $this->user?->full_name ?? $this->user?->name,
      'authorRole' => $this->author_role,
      'rating' => $this->rating,
      'content' => $this->content,
      'createdAt' => $this->created_at?->toIso8601String(),
    ];
  }
}
