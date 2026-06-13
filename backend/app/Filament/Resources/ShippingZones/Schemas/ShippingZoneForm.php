<?php

namespace App\Filament\Resources\ShippingZones\Schemas;

use App\Filament\Support\AdminFormLayout;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ShippingZoneForm
{
  /**
   * Configure le formulaire d'une zone de livraison.
   *
   * @param Schema $schema Schéma Filament à compléter
   * @param bool $hideCitySelect Masque le sélecteur de ville (contexte relation ville)
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema, bool $hideCitySelect = false): Schema
  {
    $components = [];

    if (! $hideCitySelect) {
      $components[] = Select::make('shipping_city_id')
        ->label('Ville')
        ->relationship('city', 'name')
        ->searchable()
        ->preload()
        ->required()
        ->native(false)
        ->helperText('Choisissez la ville avant d\'ajouter les communes de cette zone.');
    }

    $components = array_merge($components, [
      TextInput::make('name')
        ->label('Nom de la zone')
        ->required()
        ->maxLength(255)
        ->helperText('Ex. Centre-ville, Périphérie nord.'),
      TextInput::make('amount')
        ->label('Frais de livraison')
        ->numeric()
        ->required()
        ->minValue(0)
        ->helperText('Montant appliqué aux communes de cette zone.'),
      TextInput::make('currency')
        ->label('Devise')
        ->maxLength(3)
        ->default('CDF'),
      TextInput::make('sort_order')
        ->label('Ordre')
        ->numeric()
        ->default(0)
        ->helperText('Ordre d\'affichage dans les listes.'),
      Toggle::make('is_active')
        ->label('Active')
        ->default(true)
        ->helperText('Désactive la zone sans supprimer les communes.'),
    ]);

    return AdminFormLayout::fullWidth($schema)
      ->components([
        AdminFormLayout::section(
          'Zone',
          'Tarif appliqué aux communes rattachées dans la ville sélectionnée.',
          $components,
          1,
        ),
      ]);
  }
}
