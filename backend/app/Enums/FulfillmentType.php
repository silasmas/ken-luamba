<?php

namespace App\Enums;

/**
 * Mode de réception d'une commande physique.
 */
enum FulfillmentType: string
{
  case Delivery = 'delivery';
  case Pickup = 'pickup';

  /**
   * Libellé affiché dans l'interface.
   *
   * @return string Libellé du mode
   */
  public function label(): string
  {
    return match ($this) {
      self::Delivery => 'Livraison',
      self::Pickup => 'Retrait sur place',
    };
  }
}
