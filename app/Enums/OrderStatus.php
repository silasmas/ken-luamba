<?php

namespace App\Enums;

/**
 * Statuts du cycle de vie d'une commande.
 */
enum OrderStatus: string
{
  case PendingPayment = 'pending_payment';
  case Paid = 'paid';
  case Processing = 'processing';
  case OutForDelivery = 'out_for_delivery';
  case DeliveredByCourier = 'delivered_by_courier';
  case Completed = 'completed';
  case DeliveryDisputed = 'delivery_disputed';
  case Cancelled = 'cancelled';
  case Refunded = 'refunded';

  /**
   * Libellé affiché dans l'admin.
   *
   * @return string Libellé du statut
   */
  public function label(): string
  {
    return match ($this) {
      self::PendingPayment => 'En attente de paiement',
      self::Paid => 'Payée',
      self::Processing => 'En préparation',
      self::OutForDelivery => 'En livraison',
      self::DeliveredByCourier => 'Livrée par le livreur',
      self::Completed => 'Terminée',
      self::DeliveryDisputed => 'Litige livraison',
      self::Cancelled => 'Annulée',
      self::Refunded => 'Remboursée',
    };
  }
}
