<?php

namespace App\Enums;

/**
 * Types d'événements Ken Luamba.
 */
enum EventType: string
{
  case BookLaunch = 'book_launch';
  case Ceremony = 'ceremony';
  case Conference = 'conference';
  case Other = 'other';

  /**
   * Libellé affiché dans l'interface admin.
   *
   * @return string Libellé traduit du type
   */
  public function label(): string
  {
    return match ($this) {
      self::BookLaunch => 'Lancement de livre',
      self::Ceremony => 'Cérémonie',
      self::Conference => 'Conférence',
      self::Other => 'Autre événement',
    };
  }
}
