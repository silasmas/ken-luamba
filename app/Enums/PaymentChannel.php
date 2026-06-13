<?php

namespace App\Enums;

/**
 * Canaux de paiement FlexPay supportés.
 */
enum PaymentChannel: string
{
  case MobileMoney = 'mobile_money';
  case Card = 'card';

  /**
   * Libellé affiché dans l'interface.
   *
   * @return string Libellé du canal
   */
  public function label(): string
  {
    return match ($this) {
      self::MobileMoney => 'Mobile Money',
      self::Card => 'Carte bancaire',
    };
  }
}
