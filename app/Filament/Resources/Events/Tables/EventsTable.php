<?php

namespace App\Filament\Resources\Events\Tables;

use App\Filament\Pages\InvitationStats;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EventsTable
{
  /**
   * Configure le tableau de liste des événements.
   *
   * @param Table $table Table Filament à configurer
   * @return Table Table configurée
   */
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('title')
          ->label('Titre')
          ->searchable()
          ->sortable(),
        TextColumn::make('type')
          ->label('Type')
          ->badge()
          ->formatStateUsing(fn ($state) => $state?->label()),
        TextColumn::make('books.title')
          ->label('Livres')
          ->badge()
          ->limitList(2),
        TextColumn::make('starts_at')
          ->label('Début')
          ->dateTime('d/m/Y H:i')
          ->sortable(),
        TextColumn::make('location')
          ->label('Lieu')
          ->limit(30),
        TextColumn::make('invitations_count')
          ->label('Invités')
          ->counts('invitations'),
        TextColumn::make('attending_count')
          ->label('Présents')
          ->numeric()
          ->color('success'),
        TextColumn::make('not_attending_count')
          ->label('Absents')
          ->numeric()
          ->color('danger'),
        TextColumn::make('pending_count')
          ->label('En attente')
          ->numeric()
          ->color('warning'),
        IconColumn::make('is_published')
          ->label('Publié')
          ->boolean(),
      ])
      ->filters([
        TernaryFilter::make('is_published')
          ->label('Publié'),
      ])
      ->recordActions([
        Action::make('stats')
          ->label('Statistiques')
          ->icon(Heroicon::OutlinedChartBarSquare)
          ->url(fn ($record): string => InvitationStats::getUrl([
            'filters' => [
              'eventId' => $record->getKey(),
            ],
          ])),
        EditAction::make(),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
        ]),
      ]);
  }
}
