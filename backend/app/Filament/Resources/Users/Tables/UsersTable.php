<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
  /**
   * Configure le tableau de liste des utilisateurs.
   *
   * @param Table $table Table Filament à configurer
   * @return Table Table configurée
   */
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('full_name')
          ->label('Nom')
          ->searchable()
          ->sortable(),
        TextColumn::make('email')
          ->label('Email')
          ->searchable()
          ->sortable(),
        TextColumn::make('phone')
          ->label('Téléphone')
          ->toggleable(),
        TextColumn::make('role')
          ->label('Rôle métier')
          ->badge()
          ->formatStateUsing(fn (UserRole $state): string => $state->label())
          ->color(fn (UserRole $state): string => match ($state) {
            UserRole::Admin => 'danger',
            UserRole::Courier => 'info',
            UserRole::Editor => 'warning',
            default => 'gray',
          }),
        TextColumn::make('roles.name')
          ->label('Rôles Shield')
          ->badge()
          ->separator(','),
        IconColumn::make('is_active')
          ->label('Actif')
          ->boolean(),
        TextColumn::make('created_at')
          ->label('Créé le')
          ->dateTime('d/m/Y H:i')
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->defaultSort('created_at', 'desc')
      ->filters([
        SelectFilter::make('role')
          ->label('Rôle métier')
          ->options(collect(UserRole::cases())->mapWithKeys(
            fn (UserRole $role) => [$role->value => $role->label()]
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
