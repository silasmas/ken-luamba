<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Support\OrderAdminFormatter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentsTable
{
  /**
   * Configure le tableau de liste des paiements.
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
          ->searchable()
          ->sortable()
          ->description(fn ($record): string => OrderAdminFormatter::clientContact($record->order)),
        TextColumn::make('order_items')
          ->label('Livres commandés')
          ->state(fn ($record): string => $record->order
            ? OrderAdminFormatter::itemsSummary($record->order)
            : '—')
          ->wrap(),
        TextColumn::make('provider_reference')
          ->label('Réf. FlexPay')
          ->searchable(),
        TextColumn::make('channel')
          ->label('Canal')
          ->formatStateUsing(fn (?PaymentChannel $state) => $state?->label() ?? '—'),
        TextColumn::make('amount')
          ->label('Montant')
          ->money(fn ($record) => $record->currency)
          ->sortable(),
        TextColumn::make('status')
          ->label('Statut')
          ->badge()
          ->formatStateUsing(fn (PaymentStatus $state): string => $state->label())
          ->color(fn (PaymentStatus $state): string => match ($state) {
            PaymentStatus::Completed => 'success',
            PaymentStatus::Processing, PaymentStatus::Pending => 'warning',
            PaymentStatus::Failed, PaymentStatus::Cancelled => 'danger',
            default => 'gray',
          }),
        TextColumn::make('phone')
          ->label('Téléphone'),
        TextColumn::make('paid_at')
          ->label('Payé le')
          ->dateTime('d/m/Y H:i')
          ->sortable(),
        TextColumn::make('created_at')
          ->label('Créé le')
          ->dateTime('d/m/Y H:i')
          ->sortable(),
      ])
      ->defaultSort('created_at', 'desc')
      ->filters([
        SelectFilter::make('status')
          ->label('Statut')
          ->options(collect(PaymentStatus::cases())->mapWithKeys(
            fn (PaymentStatus $status) => [$status->value => $status->label()]
          )->all()),
        SelectFilter::make('channel')
          ->label('Canal')
          ->options(collect(PaymentChannel::cases())->mapWithKeys(
            fn (PaymentChannel $channel) => [$channel->value => $channel->label()]
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
