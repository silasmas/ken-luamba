<?php

namespace App\Filament\Resources\PickupPoints\Schemas;

use App\Filament\Support\AdminFormLayout;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PickupPointForm
{
  /**
   * Configure le formulaire d'un point de retrait.
   *
   * @param Schema $schema Schéma Filament à compléter
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    return AdminFormLayout::fullWidth($schema)
      ->components([
        AdminFormLayout::section(
          'Point de retrait',
          'Lieu où le client peut récupérer sa commande.',
          [
            TextInput::make('name')
              ->label('Nom')
              ->required()
              ->maxLength(255)
              ->helperText('Ex. Église Ken Luamba — Kinshasa.'),
            Textarea::make('address')
              ->label('Adresse')
              ->required()
              ->rows(2)
              ->columnSpanFull()
              ->helperText('Adresse complète affichée au client.'),
            TextInput::make('city')
              ->label('Ville')
              ->required()
              ->maxLength(120)
              ->helperText('Ville ou commune du point de retrait.'),
            TextInput::make('phone')
              ->label('Téléphone')
              ->tel()
              ->maxLength(20)
              ->helperText('Contact du responsable du point.'),
            Textarea::make('opening_hours')
              ->label('Horaires')
              ->rows(2)
              ->columnSpanFull()
              ->helperText('Ex. Lun–Sam 9h–17h.'),
            Toggle::make('is_active')
              ->label('Actif')
              ->default(true)
              ->helperText('Masque le point du checkout si désactivé.'),
          ],
          1,
        ),
      ]);
  }
}
