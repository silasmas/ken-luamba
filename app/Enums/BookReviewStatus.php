<?php

namespace App\Enums;

/**
 * Statut de modération d'un témoignage lecteur.
 */
enum BookReviewStatus: string
{
  case Pending = 'pending';
  case Approved = 'approved';
  case Rejected = 'rejected';

  /**
   * Libellé affiché dans l'interface admin.
   *
   * @return string Libellé du statut
   */
  public function label(): string
  {
    return match ($this) {
      self::Pending => 'En attente',
      self::Approved => 'Approuvé',
      self::Rejected => 'Refusé',
    };
  }
}
