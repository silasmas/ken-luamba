<?php

namespace App\Enums;

/**
 * Origine d'une commande (boutique classique ou paiement direct).
 */
enum OrderSource: string
{
  case Shop = 'shop';
  case DirectPayment = 'direct_payment';

  /**
   * Libellé affiché dans l'interface admin.
   *
   * @return string Libellé traduit
   */
  public function label(): string
  {
    return match ($this) {
      self::Shop => 'Boutique',
      self::DirectPayment => 'Paiement direct',
    };
  }
}
