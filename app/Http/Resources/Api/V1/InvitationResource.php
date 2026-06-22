<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API pour une invitation publique.
 */
class InvitationResource extends JsonResource
{
  /**
   * Transforme l'invitation en tableau JSON.
   *
   * @param Request $request Requête HTTP entrante
   * @return array<string, mixed> Données sérialisées
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'fullName' => $this->full_name,
      'organization' => $this->organization,
      'guestType' => $this->organization,
      'guestTypeLabel' => $this->organization,
      'rsvpStatus' => $this->rsvp_status?->value,
      'rsvpStatusLabel' => $this->rsvp_status?->label(),
      'guestMessage' => $this->guest_message,
      'respondedAt' => $this->responded_at?->toIso8601String(),
      'event' => new EventResource($this->whenLoaded('event')),
    ];
  }
}
