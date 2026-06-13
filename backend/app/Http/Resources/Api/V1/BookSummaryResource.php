<?php

namespace App\Http\Resources\Api\V1;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API résumée pour un livre (listes).
 */
class BookSummaryResource extends JsonResource
{
  /**
   * Transforme le livre en tableau JSON résumé.
   *
   * @param Request $request Requête HTTP entrante
   * @return array<string, mixed> Données sérialisées
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'title' => $this->title,
      'slug' => $this->slug,
      'description' => $this->description,
      'coverImage' => MediaUrl::fromPath($this->cover_image),
      'isFeatured' => $this->is_featured,
      'author' => new AuthorSummaryResource($this->whenLoaded('author')),
      'formats' => BookFormatResource::collection($this->whenLoaded('formats')),
    ];
  }
}
