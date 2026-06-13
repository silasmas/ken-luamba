<?php

namespace App\Enums;

/**
 * Types de remise applicables sur une commande.
 */
enum DiscountType: string
{
  case Percentage = 'percentage';
  case FixedAmount = 'fixed_amount';

  /**
   * Libellé affiché dans l'interface admin.
   *
   * @return string Libellé du type de remise
   */
  public function label(): string
  {
    return match ($this) {
      self::Percentage => 'Pourcentage',
      self::FixedAmount => 'Montant fixe',
    };
  }
}
