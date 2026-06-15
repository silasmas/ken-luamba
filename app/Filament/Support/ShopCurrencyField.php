<?php

namespace App\Filament\Support;

use App\Enums\ShopCurrency;
use Filament\Forms\Components\Select;

/**
 * Champ Filament réutilisable pour choisir CDF ou USD.
 */
class ShopCurrencyField
{
  /**
   * Construit un sélecteur de devise boutique.
   *
   * @param string $name Nom du champ
   * @param string $label Libellé affiché
   * @return Select Composant Filament configuré
   */
  public static function select(string $name = 'currency', string $label = 'Devise'): Select
  {
    return Select::make($name)
      ->label($label)
      ->options(ShopCurrency::selectOptions())
      ->default(ShopCurrency::Cdf->value)
      ->required()
      ->native(false)
      ->live();
  }
}
