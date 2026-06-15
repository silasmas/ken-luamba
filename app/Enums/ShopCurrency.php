<?php

namespace App\Enums;

/**
 * Devises acceptées dans la boutique (prix, commandes, livraison).
 */
enum ShopCurrency: string
{
  case Cdf = 'CDF';
  case Usd = 'USD';

  /**
   * Libellé affiché dans l'interface admin et la boutique.
   *
   * @return string Nom lisible de la devise
   */
  public function label(): string
  {
    return match ($this) {
      self::Cdf => 'Franc congolais (CDF)',
      self::Usd => 'Dollar américain (USD)',
    };
  }

  /**
   * Retourne les options pour un champ Select Filament.
   *
   * @return array<string, string> Valeur => libellé
   */
  public static function selectOptions(): array
  {
    return collect(self::cases())
      ->mapWithKeys(fn (self $currency): array => [$currency->value => $currency->label()])
      ->all();
  }
}
