<?php

namespace App\Filament\Resources\BookReleaseSubscriptions\Tables;

use App\Filament\Support\BookReleaseAdminActions;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\IconColumn;
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
        IconColumn::make('notified_at')
          ->label('Notifié')
          ->boolean()
          ->getStateUsing(fn ($record): bool => $record->notified_at !== null),
        TextColumn::make('notified_at')
          ->label('Dernier envoi')
          ->dateTime('d/m/Y H:i')
          ->placeholder('—')
          ->toggleable(),
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
      ->recordActions([
        BookReleaseAdminActions::sendEmail(),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          BookReleaseAdminActions::sendEmailBulk(),
        ]),
      ])
      ->defaultSort('created_at', 'desc');
  }
}
