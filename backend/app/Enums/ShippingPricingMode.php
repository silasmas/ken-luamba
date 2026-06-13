<?php

namespace App\Enums;

/**
 * Mode de calcul des frais de livraison nationaux.
 */
enum ShippingPricingMode: string
{
  case Fixed = 'fixed';
  case Zone = 'zone';

  /**
   * Libellé affiché dans l'interface admin.
   *
   * @return string Libellé du mode
   */
  public function label(): string
  {
    return match ($this) {
      self::Fixed => 'Prix fixe national',
      self::Zone => 'Prix par zone / commune',
    };
  }
}
