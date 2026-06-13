<?php

namespace App\Filament\Resources\PickupPoints\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PickupPointsTable
{
  /**
   * Configure le tableau de liste des points de retrait.
   *
   * @param Table $table Table Filament à configurer
   * @return Table Table configurée
   */
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('name')
          ->label('Nom')
          ->searchable()
          ->sortable(),
        TextColumn::make('address')
          ->label('Adresse')
          ->limit(40),
        TextColumn::make('city')
          ->label('Ville')
          ->searchable()
          ->sortable(),
        TextColumn::make('phone')
          ->label('Téléphone'),
        IconColumn::make('is_active')
          ->label('Actif')
          ->boolean(),
      ])
      ->filters([
        TernaryFilter::make('is_active')
          ->label('Actif'),
      ])
      ->recordActions([
        EditAction::make(),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
        ]),
      ]);
  }
}
