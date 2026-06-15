<?php

namespace App\Enums;

/**
 * Statut d'un envoi d'alerte sortie livre.
 */
enum BookReleaseDispatchStatus: string
{
  case Sent = 'sent';
  case Failed = 'failed';
  case Scheduled = 'scheduled';

  /**
   * Libellé affiché dans l'admin.
   *
   * @return string Libellé français
   */
  public function label(): string
  {
    return match ($this) {
      self::Sent => 'Envoyé',
      self::Failed => 'Échec',
      self::Scheduled => 'Programmé',
    };
  }
}
