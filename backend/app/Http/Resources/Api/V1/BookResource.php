<?php

namespace App\Http\Resources\Api\V1;

use App\Services\BookCatalogService;
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
    $catalogService = app(BookCatalogService::class);
    $availabilityStatus = $catalogService->availabilityStatus($this->resource);

    $approvedReviews = $this->relationLoaded('approvedReviews')
      ? $this->approvedReviews
      : collect();

    $reviewCount = $approvedReviews->count();
    $averageRating = $reviewCount > 0
      ? round((float) $approvedReviews->avg('rating'), 1)
      : null;

    return [
      'id' => $this->id,
      'title' => $this->title,
      'subtitle' => $this->subtitle,
      'tagline' => $this->tagline,
      'slug' => $this->slug,
      'description' => $this->description,
      'authorNote' => $this->author_note,
      'coverImage' => MediaUrl::fromPath($this->cover_image),
      'isFeatured' => $this->is_featured,
      'publishedAt' => $this->published_at?->toIso8601String(),
      'category' => $this->category,
      'pageCount' => $this->page_count,
      'readingTime' => $catalogService->formatReadingTime($this->reading_time_minutes),
      'language' => $this->language,
      'releaseDate' => $this->release_date?->toDateString(),
      'themes' => $this->themes ?? [],
      'aboutParagraphs' => $this->about_paragraphs ?? [],
      'excerpt' => $this->excerpt ?? [],
      'accentColor' => $this->accent_color,
      'availabilityStatus' => $availabilityStatus,
      'availabilityLabel' => $catalogService->availabilityLabel($availabilityStatus),
      'preorderCampaign' => $this->when($availabilityStatus === 'preorder', [
        'goal' => $this->preorder_campaign_goal,
        'reserved' => $this->preorder_reserved_count,
        'bonuses' => $this->preorder_campaign_bonuses ?? [],
      ]),
      'reviewStats' => [
        'count' => $reviewCount,
        'averageRating' => $averageRating,
      ],
      'reviews' => BookReviewResource::collection($this->whenLoaded('approvedReviews')),
      'relatedBooks' => BookSummaryResource::collection($this->whenLoaded('relatedBooks')),
      'author' => new AuthorSummaryResource($this->whenLoaded('author')),
      'formats' => BookFormatResource::collection($this->whenLoaded('formats')),
    ];
  }
}
