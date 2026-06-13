<?php

namespace App\Filament\Resources\ShippingZones\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ShippingZonesTable
{
  /**
   * Configure le tableau des zones de livraison.
   *
   * @param Table $table Table Filament à configurer
   * @return Table Table configurée
   */
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('city.name')
          ->label('Ville')
          ->searchable()
          ->sortable(),
        TextColumn::make('name')
          ->label('Zone')
          ->searchable()
          ->sortable(),
        TextColumn::make('amount')
          ->label('Frais')
          ->money(fn ($record) => $record->currency)
          ->sortable(),
        TextColumn::make('communes_count')
          ->label('Communes')
          ->counts('communes'),
        IconColumn::make('is_active')
          ->label('Active')
          ->boolean(),
        TextColumn::make('sort_order')
          ->label('Ordre')
          ->sortable(),
      ])
      ->defaultSort('sort_order')
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
