<?php

namespace App\Enums;

/**
 * Statut d'un envoi de message d'invitation.
 */
enum InvitationDispatchStatus: string
{
  case Sent = 'sent';
  case Failed = 'failed';

  /**
   * Libellé affiché dans l'interface admin.
   *
   * @return string Libellé du statut
   */
  public function label(): string
  {
    return match ($this) {
      self::Sent => 'Envoyé',
      self::Failed => 'Échec',
    };
  }
}
