<?php

namespace App\Enums;

/**
 * Politique de fret pour les livraisons hors du pays.
 */
enum InternationalShippingPolicy: string
{
  case Fixed = 'fixed';
  case Quote = 'quote';
  case Unavailable = 'unavailable';

  /**
   * Libellé affiché dans l'interface admin.
   *
   * @return string Libellé de la politique
   */
  public function label(): string
  {
    return match ($this) {
      self::Fixed => 'Montant fixe international',
      self::Quote => 'Sur devis (contact client)',
      self::Unavailable => 'Livraison internationale indisponible',
    };
  }
}
