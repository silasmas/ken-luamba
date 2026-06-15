<?php

namespace App\Filament\Resources\ShopSettings\Schemas;

use App\Filament\Support\AdminFormLayout;
use App\Filament\Support\ShopCurrencyField;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Schema;

class ShopSettingForm
{
  /**
   * Configure le formulaire des paramètres boutique.
   *
   * @param Schema $schema Schéma Filament à compléter
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    return AdminFormLayout::fullWidth($schema)
      ->components([
        AdminFormLayout::section(
          'Devise',
          'Monnaie unique affichée sur la boutique et utilisée pour les tarifs, commandes et livraisons.',
          [
            ShopCurrencyField::select()
              ->helperText('CDF pour le marché local, USD pour les ventes internationales. Les prix existants dans une autre devise ne seront plus visibles tant qu\'ils ne sont pas recréés dans cette devise.'),
            Placeholder::make('currency_notice')
              ->label('Impact')
              ->content('Après changement, vérifiez les périodes tarifaires et les frais de livraison : ils doivent être saisis dans la devise choisie.'),
          ],
          1,
        ),
      ]);
  }
}
