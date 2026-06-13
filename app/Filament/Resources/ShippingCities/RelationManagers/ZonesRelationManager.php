<?php

namespace App\Filament\Resources\ShippingCities\RelationManagers;

use App\Filament\Resources\ShippingZones\Schemas\ShippingZoneForm;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ZonesRelationManager extends RelationManager
{
  protected static string $relationship = 'zones';

  protected static ?string $title = 'Zones tarifaires';

  /**
   * Configure le formulaire d'une zone rattachée à la ville.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public function form(Schema $schema): Schema
  {
    return ShippingZoneForm::configure($schema, hideCitySelect: true);
  }

  /**
   * Configure le tableau des zones de la ville.
   *
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public function table(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('name')
          ->label('Zone')
          ->searchable(),
        TextColumn::make('amount')
          ->label('Frais')
          ->money(fn ($record) => $record->currency),
        TextColumn::make('communes_count')
          ->label('Communes')
          ->counts('communes'),
        IconColumn::make('is_active')
          ->label('Active')
          ->boolean(),
      ])
      ->headerActions([
        CreateAction::make(),
      ])
      ->recordActions([
        EditAction::make(),
        DeleteAction::make(),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
        ]),
      ]);
  }
}
