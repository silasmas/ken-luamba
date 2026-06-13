<?php

namespace App\Filament\Resources\QuantityDiscounts\Tables;

use App\Enums\DiscountAppliesTo;
use App\Enums\DiscountType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuantityDiscountsTable
{
  /**
   * Configure le tableau des remises par quantité.
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
          ->searchable(),
        TextColumn::make('min_quantity')
          ->label('Qté min.')
          ->sortable(),
        TextColumn::make('discount_type')
          ->label('Type')
          ->formatStateUsing(fn (DiscountType $state): string => $state->label()),
        TextColumn::make('discount_value')
          ->label('Valeur'),
        TextColumn::make('applies_to')
          ->label('Portée')
          ->formatStateUsing(fn (DiscountAppliesTo $state): string => $state->label()),
        TextColumn::make('book.title')
          ->label('Livre')
          ->placeholder('—'),
        IconColumn::make('is_active')
          ->label('Active')
          ->boolean(),
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
