<?php

namespace App\Filament\Resources\Authors\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuthorsTable
{
  /**
   * Configure le tableau de liste des auteurs.
   *
   * @param Table $table Table Filament à configurer
   * @return Table Table configurée
   */
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        ImageColumn::make('profile_image')
          ->label('Photo')
          ->circular(),
        TextColumn::make('full_name')
          ->label('Nom')
          ->searchable()
          ->sortable(),
        TextColumn::make('slug')
          ->label('Slug')
          ->searchable(),
        IconColumn::make('is_primary')
          ->label('Principal')
          ->boolean(),
        IconColumn::make('is_published')
          ->label('Publié')
          ->boolean(),
        TextColumn::make('books_count')
          ->label('Livres')
          ->counts('books'),
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
