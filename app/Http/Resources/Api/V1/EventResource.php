<?php

namespace App\Http\Resources\Api\V1;

use App\Support\MediaUrl;
use App\Services\Invitations\InvitationShareImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API pour un événement public.
 */
class EventResource extends JsonResource
{
  /**
   * Transforme l'événement en tableau JSON.
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
      'type' => $this->type?->value,
      'typeLabel' => $this->type?->label(),
      'description' => $this->description,
      'welcomeMessage' => $this->welcome_message,
      'startsAt' => $this->starts_at?->toIso8601String(),
      'endsAt' => $this->ends_at?->toIso8601String(),
      'location' => $this->location,
      'venueDetails' => $this->venue_details,
      'books' => $this->whenLoaded('books', fn () => $this->books->map(fn ($book) => [
        'title' => $book->title,
        'slug' => $book->slug,
        'coverImage' => MediaUrl::fromPath($book->cover_image),
      ])->values()->all()),
      'coverImages' => $this->whenLoaded('books', fn () => $this->books
        ->map(fn ($book) => MediaUrl::fromPath($book->cover_image))
        ->filter()
        ->values()
        ->all()),
      'shareImageUrl' => $this->whenLoaded('books', fn () => app(InvitationShareImageService::class)->urlForEvent($this->resource)),
    ];
  }
}
