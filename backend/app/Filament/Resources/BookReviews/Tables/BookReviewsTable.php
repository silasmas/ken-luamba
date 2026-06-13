<?php

namespace App\Filament\Resources\BookReviews\Tables;

use App\Enums\BookReviewStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BookReviewsTable
{
  /**
   * Configure le tableau de modération des témoignages.
   *
   * @param Table $table Table Filament à configurer
   * @return Table Table configurée
   */
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('book.title')
          ->label('Livre')
          ->searchable()
          ->sortable(),
        TextColumn::make('user.full_name')
          ->label('Lecteur')
          ->searchable(),
        TextColumn::make('rating')
          ->label('Note')
          ->sortable(),
        TextColumn::make('content')
          ->label('Témoignage')
          ->limit(60),
        TextColumn::make('status')
          ->label('Statut')
          ->badge()
          ->formatStateUsing(fn (BookReviewStatus $state): string => $state->label())
          ->color(fn (BookReviewStatus $state): string => match ($state) {
            BookReviewStatus::Approved => 'success',
            BookReviewStatus::Rejected => 'danger',
            default => 'warning',
          }),
        TextColumn::make('created_at')
          ->label('Soumis le')
          ->dateTime('d/m/Y H:i')
          ->sortable(),
      ])
      ->defaultSort('created_at', 'desc')
      ->filters([
        SelectFilter::make('status')
          ->label('Statut')
          ->options(collect(BookReviewStatus::cases())->mapWithKeys(
            fn (BookReviewStatus $status) => [$status->value => $status->label()],
          )->all()),
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
