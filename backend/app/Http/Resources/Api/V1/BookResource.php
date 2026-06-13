<?php

namespace App\Http\Resources\Api\V1;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API détaillée pour un livre.
 */
class BookResource extends JsonResource
{
  /**
   * Transforme le livre en tableau JSON détaillé.
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
      'authorNote' => $this->author_note,
      'coverImage' => MediaUrl::fromPath($this->cover_image),
      'isFeatured' => $this->is_featured,
      'publishedAt' => $this->published_at?->toIso8601String(),
      'author' => new AuthorSummaryResource($this->whenLoaded('author')),
      'formats' => BookFormatResource::collection($this->whenLoaded('formats')),
    ];
  }
}
