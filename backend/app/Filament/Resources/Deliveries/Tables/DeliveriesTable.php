<?php

namespace App\Filament\Resources\Deliveries\Tables;

use App\Enums\DeliveryStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DeliveriesTable
{
  /**
   * Configure le tableau de liste des livraisons.
   *
   * @param Table $table Table Filament à configurer
   * @return Table Table configurée
   */
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('order.order_number')
          ->label('Commande')
          ->searchable()
          ->sortable(),
        TextColumn::make('order.user.full_name')
          ->label('Client')
          ->searchable(),
        TextColumn::make('courier.full_name')
          ->label('Livreur')
          ->placeholder('—'),
        TextColumn::make('status')
          ->label('Statut')
          ->badge()
          ->formatStateUsing(fn (DeliveryStatus $state): string => $state->label())
          ->color(fn (DeliveryStatus $state): string => match ($state) {
            DeliveryStatus::Delivered, DeliveryStatus::PickedUp => 'success',
            DeliveryStatus::OutForDelivery, DeliveryStatus::Assigned => 'info',
            DeliveryStatus::Disputed => 'danger',
            default => 'gray',
          }),
        TextColumn::make('assigned_at')
          ->label('Assignée le')
          ->dateTime('d/m/Y H:i')
          ->sortable(),
        TextColumn::make('delivered_at')
          ->label('Livrée le')
          ->dateTime('d/m/Y H:i')
          ->sortable(),
      ])
      ->defaultSort('created_at', 'desc')
      ->filters([
        SelectFilter::make('status')
          ->label('Statut')
          ->options(collect(DeliveryStatus::cases())->mapWithKeys(
            fn (DeliveryStatus $status) => [$status->value => $status->label()]
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
