<?php

namespace App\Filament\Resources\PricingPeriods\Tables;

use App\Enums\PricingPeriodType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PricingPeriodsTable
{
  /**
   * Configure le tableau des périodes tarifaires.
   *
   * @param Table $table Table Filament à configurer
   * @return Table Table configurée
   */
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('bookFormat.book.title')
          ->label('Livre')
          ->searchable()
          ->sortable(),
        TextColumn::make('bookFormat.type')
          ->label('Format')
          ->formatStateUsing(fn ($state): string => $state->label()),
        TextColumn::make('label')
          ->label('Libellé')
          ->searchable(),
        TextColumn::make('type')
          ->label('Type')
          ->formatStateUsing(fn (PricingPeriodType $state): string => $state->label()),
        TextColumn::make('price')
          ->label('Prix')
          ->money('CDF'),
        TextColumn::make('start_at')
          ->label('Début')
          ->dateTime('d/m/Y H:i'),
        TextColumn::make('end_at')
          ->label('Fin')
          ->dateTime('d/m/Y H:i'),
        IconColumn::make('is_active')
          ->label('Active')
          ->boolean(),
      ])
      ->defaultSort('start_at', 'desc')
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
