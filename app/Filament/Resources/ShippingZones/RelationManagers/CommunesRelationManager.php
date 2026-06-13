<?php

namespace App\Filament\Resources\ShippingZones\RelationManagers;

use App\Filament\Support\AdminFormLayout;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommunesRelationManager extends RelationManager
{
  protected static string $relationship = 'communes';

  protected static ?string $title = 'Communes';

  /**
   * Configure le formulaire d'une commune de zone.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public function form(Schema $schema): Schema
  {
    return AdminFormLayout::fullWidth($schema)
      ->components([
        TextInput::make('name')
          ->label('Commune')
          ->required()
          ->maxLength(120)
          ->helperText('Nom exact de la commune (ex. Gombe, Limete).'),
      ]);
  }

  /**
   * Configure le tableau des communes rattachées.
   *
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public function table(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('name')
          ->label('Commune')
          ->searchable(),
        TextColumn::make('city')
          ->label('Ville')
          ->placeholder('—'),
      ])
      ->headerActions([
        CreateAction::make()
          ->mutateFormDataUsing(function (array $data): array {
            $data['city'] = $this->getOwnerRecord()->city?->name;

            return $data;
          }),
      ])
      ->recordActions([
        EditAction::make(),
        DeleteAction::make(),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
        ]),
      ]);
  }
}
