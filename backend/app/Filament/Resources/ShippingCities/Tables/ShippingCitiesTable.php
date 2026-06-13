<?php

namespace App\Filament\Resources\ShippingCities\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ShippingCitiesTable
{
  /**
   * Configure le tableau des villes de livraison.
   *
   * @param Table $table Table Filament à configurer
   * @return Table Table configurée
   */
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('name')
          ->label('Ville')
          ->searchable()
          ->sortable(),
        IconColumn::make('is_delivery_available')
          ->label('Livraison')
          ->boolean()
          ->trueIcon('heroicon-o-check-circle')
          ->falseIcon('heroicon-o-x-circle'),
        TextColumn::make('zones_count')
          ->label('Zones')
          ->counts('zones'),
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
