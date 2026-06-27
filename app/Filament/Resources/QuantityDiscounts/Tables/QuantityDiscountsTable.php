<?php

namespace App\Filament\Resources\QuantityDiscounts\Tables;

use App\Enums\DiscountAppliesTo;
use App\Enums\DiscountType;
use App\Models\QuantityDiscount;
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
          ->label('Mode de comptage')
          ->formatStateUsing(fn (DiscountAppliesTo $state): string => $state->label())
          ->description(fn (QuantityDiscount $record): ?string => match ($record->applies_to) {
            DiscountAppliesTo::DistinctPhysicalBooks => "Seuil : {$record->min_quantity} titre(s) distinct(s)",
            DiscountAppliesTo::SinglePhysicalTitle => "Seuil : {$record->min_quantity} exemplaire(s) d'un même titre",
            DiscountAppliesTo::AuthorCompleteSet => 'Collection complète auteur',
            DiscountAppliesTo::PhysicalOnly, DiscountAppliesTo::AllBooks => "Seuil : {$record->min_quantity} exemplaire(s) au total",
            DiscountAppliesTo::SpecificBook => "Seuil : {$record->min_quantity}× « {$record->book?->title} »",
          }),
        TextColumn::make('book.title')
          ->label('Livre')
          ->placeholder('—'),
        TextColumn::make('author.full_name')
          ->label('Auteur')
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
