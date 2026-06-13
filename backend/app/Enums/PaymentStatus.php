<?php

namespace App\Enums;

/**
 * Statuts d'une transaction de paiement.
 */
enum PaymentStatus: string
{
  case Pending = 'pending';
  case Processing = 'processing';
  case Completed = 'completed';
  case Failed = 'failed';
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
      self::Pending => 'En attente',
      self::Processing => 'En cours',
      self::Completed => 'Payé',
      self::Failed => 'Échoué',
      self::Cancelled => 'Annulé',
      self::Refunded => 'Remboursé',
    };
  }
}
