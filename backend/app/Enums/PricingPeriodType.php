<?php

namespace App\Enums;

/**
 * Types de périodes tarifaires pour un format de livre.
 */
enum PricingPeriodType: string
{
  case Preorder = 'preorder';
  case Regular = 'regular';
  case Promo = 'promo';

  /**
   * Libellé affiché dans l'interface admin.
   *
   * @return string Libellé de la période
   */
  public function label(): string
  {
    return match ($this) {
      self::Preorder => 'Pré-commande',
      self::Regular => 'Vente régulière',
      self::Promo => 'Promotion',
    };
  }
}
