<?php

namespace App\Enums;

/**
 * Statuts de réponse RSVP d'une invitation.
 */
enum InvitationRsvpStatus: string
{
  case Pending = 'pending';
  case Attending = 'attending';
  case NotAttending = 'not_attending';

  /**
   * Libellé affiché dans l'interface admin.
   *
   * @return string Libellé traduit du statut
   */
  public function label(): string
  {
    return match ($this) {
      self::Pending => 'En attente',
      self::Attending => 'Présent',
      self::NotAttending => 'Absent',
    };
  }
}
