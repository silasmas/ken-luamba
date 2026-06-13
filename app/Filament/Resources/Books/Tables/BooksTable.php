<?php

namespace App\Filament\Resources\Books\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class BooksTable
{
  /**
   * Configure le tableau de liste des livres.
   *
   * @param Table $table Table Filament à configurer
   * @return Table Table configurée
   */
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        ImageColumn::make('cover_image')
          ->label('Couverture')
          ->disk('public')
          ->visibility('public')
          ->checkFileExistence(false),
        TextColumn::make('title')
          ->label('Titre')
          ->searchable()
          ->sortable(),
        TextColumn::make('author.full_name')
          ->label('Auteur')
          ->sortable(),
        IconColumn::make('is_published')
          ->label('Publié')
          ->boolean(),
        IconColumn::make('is_featured')
          ->label('À la une')
          ->boolean(),
        TextColumn::make('formats_count')
          ->label('Formats')
          ->counts('formats'),
        TextColumn::make('sort_order')
          ->label('Ordre')
          ->sortable(),
      ])
      ->filters([
        TernaryFilter::make('is_published')
          ->label('Publié'),
        TernaryFilter::make('is_featured')
          ->label('Mis en avant'),
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
