<?php

namespace App\Filament\Resources\BookReleaseSubscriptions\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Tableau Filament des inscriptions alerte sortie.
 */
class BookReleaseSubscriptionsTable
{
  /**
   * Configure le tableau des alertes sortie.
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
        TextColumn::make('email')
          ->label('E-mail')
          ->searchable()
          ->copyable(),
        TextColumn::make('created_at')
          ->label('Inscrit le')
          ->dateTime('d/m/Y H:i')
          ->sortable(),
      ])
      ->filters([
        SelectFilter::make('book_id')
          ->label('Livre')
          ->relationship('book', 'title'),
      ])
      ->defaultSort('created_at', 'desc');
  }
}
