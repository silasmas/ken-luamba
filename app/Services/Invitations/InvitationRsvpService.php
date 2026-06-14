<?php

namespace App\Services\Invitations;

use App\Enums\InvitationRsvpStatus;
use App\Models\Invitation;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Gère les réponses RSVP des invités.
 */
class InvitationRsvpService
{
  /**
   * Enregistre la réponse d'un invité via son token public.
   *
   * @param string $token Token d'invitation
   * @param InvitationRsvpStatus $status Statut choisi
   * @param string|null $message Message optionnel de l'invité
   * @return Invitation Invitation mise à jour
   */
  public function respond(string $token, InvitationRsvpStatus $status, ?string $message = null): Invitation
  {
    $invitation = Invitation::query()
      ->with(['event.books'])
      ->where('token', $token)
      ->first();

    if ($invitation === null) {
      throw new ModelNotFoundException('Invitation introuvable.');
    }

    if ($invitation->event === null || ! $invitation->event->is_published) {
      throw new ModelNotFoundException('Événement indisponible.');
    }

    $invitation->update([
      'rsvp_status' => $status,
      'guest_message' => $message !== null && trim($message) !== '' ? trim($message) : null,
      'responded_at' => now(),
    ]);

    return $invitation->fresh(['event.books']);
  }
}
