<?php

namespace App\Filament\Resources\ShippingCities\Schemas;

use App\Filament\Support\AdminFormLayout;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ShippingCityForm
{
  /**
   * Configure le formulaire d'une ville de livraison.
   *
   * @param Schema $schema Schéma Filament à compléter
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    return AdminFormLayout::fullWidth($schema)
      ->components([
        AdminFormLayout::section(
          'Ville',
          'Activez la livraison par ville avant de configurer les zones et communes.',
          [
            TextInput::make('name')
              ->label('Nom de la ville')
              ->required()
              ->maxLength(120)
              ->unique(ignoreRecord: true)
              ->helperText('Ex. Kinshasa, Lubumbashi, Goma.'),
            Toggle::make('is_delivery_available')
              ->label('Livraison disponible')
              ->default(false)
              ->helperText('Si désactivé, les clients ne pourront pas commander une livraison dans cette ville.'),
            TextInput::make('sort_order')
              ->label('Ordre')
              ->numeric()
              ->default(0)
              ->helperText('Ordre d\'affichage dans les listes et le checkout.'),
          ],
          1,
        ),
      ]);
  }
}
