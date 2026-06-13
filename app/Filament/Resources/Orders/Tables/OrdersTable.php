<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
  /**
   * Configure le tableau de liste des commandes.
   *
   * @param Table $table Table Filament à configurer
   * @return Table Table configurée
   */
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('order_number')
          ->label('N° commande')
          ->searchable()
          ->sortable(),
        TextColumn::make('user.full_name')
          ->label('Client')
          ->searchable()
          ->sortable(),
        TextColumn::make('status')
          ->label('Statut')
          ->badge()
          ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
          ->color(fn (OrderStatus $state): string => match ($state) {
            OrderStatus::Paid, OrderStatus::Completed => 'success',
            OrderStatus::PendingPayment => 'warning',
            OrderStatus::Cancelled, OrderStatus::Refunded => 'danger',
            default => 'gray',
          }),
        TextColumn::make('fulfillment_type')
          ->label('Réception')
          ->formatStateUsing(fn ($state) => $state?->label() ?? '—'),
        TextColumn::make('total')
          ->label('Total')
          ->money(fn ($record) => $record->currency)
          ->sortable(),
        TextColumn::make('paid_at')
          ->label('Payée le')
          ->dateTime('d/m/Y H:i')
          ->sortable(),
        TextColumn::make('created_at')
          ->label('Créée le')
          ->dateTime('d/m/Y H:i')
          ->sortable(),
      ])
      ->defaultSort('created_at', 'desc')
      ->filters([
        SelectFilter::make('status')
          ->label('Statut')
          ->options(collect(OrderStatus::cases())->mapWithKeys(
            fn (OrderStatus $status) => [$status->value => $status->label()]
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
