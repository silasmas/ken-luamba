<?php

namespace App\Http\Resources\Api\V1;

use App\Services\BookCatalogService;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API résumée pour un livre (listes et accueil).
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
    $catalogService = app(BookCatalogService::class);
    $availabilityStatus = $catalogService->availabilityStatus($this->resource);

    return [
      'id' => $this->id,
      'title' => $this->title,
      'subtitle' => $this->subtitle,
      'tagline' => $this->tagline,
      'slug' => $this->slug,
      'description' => $this->description,
      'coverImage' => MediaUrl::fromPath($this->cover_image),
      'accentColor' => $this->accent_color,
      'isFeatured' => $this->is_featured,
      'pageCount' => $this->page_count,
      'readingTime' => $catalogService->formatReadingTime($this->reading_time_minutes),
      'language' => $this->language,
      'releaseDate' => $this->release_date?->toDateString(),
      'preorderEndsAt' => $catalogService->preorderEndsAt($this->resource)?->toIso8601String(),
      'availabilityStatus' => $availabilityStatus,
      'availabilityLabel' => $catalogService->availabilityLabel($availabilityStatus),
      'preorderCampaign' => $this->preorder_campaign_goal ? [
        'goal' => $this->preorder_campaign_goal,
        'reserved' => $this->preorder_reserved_count,
        'bonuses' => $this->preorder_campaign_bonuses ?? [],
      ] : null,
      'author' => new AuthorSummaryResource($this->whenLoaded('author')),
      'formats' => BookFormatResource::collection($this->whenLoaded('formats')),
    ];
  }
}
