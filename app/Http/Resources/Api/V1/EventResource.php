<?php

namespace App\Http\Resources\Api\V1;

use App\Services\Books\BookCoverService;
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
      'startsAt' => $this->starts_at?->timezone(config('app.timezone'))->toIso8601String(),
      'endsAt' => $this->ends_at?->timezone(config('app.timezone'))->toIso8601String(),
      'displayTimezone' => config('app.timezone'),
      'location' => $this->location,
      'venueDetails' => $this->venue_details,
      'books' => $this->whenLoaded('books', fn () => $this->serializeAssociatedBooks()),
      'coverImages' => $this->whenLoaded('books', fn () => collect($this->serializeAssociatedBooks())
        ->pluck('coverImage')
        ->filter()
        ->values()
        ->all()),
      'shareImageUrl' => $this->whenLoaded('books', fn () => app(InvitationShareImageService::class)->urlForEvent($this->resource)),
    ];
  }

  /**
   * Sérialise uniquement les livres enregistrés dans la table pivot book_event.
   *
   * @return list<array{id: string, title: string, slug: string, coverImage: string|null}> Livres associés
   */
  private function serializeAssociatedBooks(): array
  {
    $coverService = app(BookCoverService::class);

    return $this->books
      ->unique('id')
      ->values()
      ->map(fn ($book) => [
        'id' => $book->id,
        'title' => $book->title,
        'slug' => $book->slug,
        'coverImage' => $coverService->url($book),
      ])
      ->all();
  }
}
