<?php

namespace App\Enums;

/**
 * Statuts du suivi de livraison ou retrait.
 */
enum DeliveryStatus: string
{
  case Pending = 'pending';
  case Assigned = 'assigned';
  case OutForDelivery = 'out_for_delivery';
  case Delivered = 'delivered';
  case PickedUp = 'picked_up';
  case Disputed = 'disputed';

  /**
   * Libellé affiché dans l'interface.
   *
   * @return string Libellé du statut
   */
  public function label(): string
  {
    return match ($this) {
      self::Pending => 'En attente',
      self::Assigned => 'Assignée',
      self::OutForDelivery => 'En cours',
      self::Delivered => 'Livrée',
      self::PickedUp => 'Retirée',
      self::Disputed => 'Litige',
    };
  }
}
