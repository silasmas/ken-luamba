<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Support\OrderAdminFormatter;
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
          ->sortable()
          ->description(fn ($record): string => OrderAdminFormatter::clientContact($record))
          ->toggleable(),
        TextColumn::make('items_summary')
          ->label('Articles')
          ->state(fn ($record) => OrderAdminFormatter::itemsSummaryHtml($record))
          ->html()
          ->wrap()
          ->tooltip(fn ($record): ?string => OrderAdminFormatter::itemsSummary($record) !== '—'
            ? OrderAdminFormatter::itemsSummary($record)
            : null)
          ->searchable(query: function ($query, string $search): void {
            $query->whereHas('items', fn ($items) => $items->where('book_title', 'like', "%{$search}%"));
          }),
        TextColumn::make('status')
          ->label('Statut')
          ->badge()
          ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
          ->color(fn (OrderStatus $state): string => match ($state) {
            OrderStatus::Paid, OrderStatus::Completed => 'success',
            OrderStatus::PendingPayment => 'warning',
            OrderStatus::Cancelled, OrderStatus::Refunded => 'danger',
            default => 'gray',
          })
          ->toggleable(),
        TextColumn::make('fulfillment_type')
          ->label('Réception')
          ->formatStateUsing(fn ($state) => $state?->label() ?? '—')
          ->toggleable(),
        TextColumn::make('total')
          ->label('Total')
          ->money(fn ($record) => $record->currency)
          ->sortable()
          ->toggleable(),
        TextColumn::make('paid_at')
          ->label('Payée le')
          ->dateTime('d/m/Y H:i')
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('created_at')
          ->label('Créée le')
          ->dateTime('d/m/Y H:i')
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
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
