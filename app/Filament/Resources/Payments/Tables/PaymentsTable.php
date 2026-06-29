<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Models\User;
use App\Support\OrderAdminFormatter;
use App\Support\OrderBooksReceivedQuery;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

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
          ->label('Client payeur')
          ->searchable()
          ->sortable()
          ->description(fn ($record): string => $record->order
            ? OrderAdminFormatter::clientContact($record->order)
            : '—')
          ->toggleable(),
        TextColumn::make('order_items')
          ->label('Livres commandés')
          ->state(fn ($record) => $record->order
            ? OrderAdminFormatter::itemsSummaryHtml($record->order)
            : new HtmlString('<span class="text-gray-400">—</span>'))
          ->html()
          ->wrap()
          ->tooltip(fn ($record): ?string => $record->order && OrderAdminFormatter::itemsSummary($record->order) !== '—'
            ? OrderAdminFormatter::itemsSummary($record->order)
            : null),
        TextColumn::make('order.books_received')
          ->label('Livre reçu')
          ->state(fn ($record): string => $record->order
            ? OrderAdminFormatter::booksReceivedLabel($record->order)
            : '—')
          ->badge()
          ->color(fn ($record): string => match ($record->order
            ? OrderAdminFormatter::booksReceivedLabel($record->order)
            : '—') {
            'Reçu' => 'success',
            'Non reçu' => 'warning',
            default => 'gray',
          })
          ->toggleable(),
        TextColumn::make('provider_reference')
          ->label('Réf. FlexPay')
          ->searchable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('channel')
          ->label('Canal')
          ->formatStateUsing(fn (?PaymentChannel $state) => $state?->label() ?? '—')
          ->toggleable(),
        TextColumn::make('amount')
          ->label('Montant')
          ->money(fn ($record) => $record->currency)
          ->sortable()
          ->toggleable(),
        TextColumn::make('status')
          ->label('Statut')
          ->badge()
          ->formatStateUsing(fn (PaymentStatus $state): string => $state->label())
          ->color(fn (PaymentStatus $state): string => match ($state) {
            PaymentStatus::Completed => 'success',
            PaymentStatus::Processing, PaymentStatus::Pending => 'warning',
            PaymentStatus::Failed, PaymentStatus::Cancelled => 'danger',
            default => 'gray',
          })
          ->toggleable(),
        TextColumn::make('phone')
          ->label('Téléphone')
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('paid_at')
          ->label('Payé le')
          ->dateTime('d/m/Y H:i')
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('created_at')
          ->label('Créé le')
          ->dateTime('d/m/Y H:i')
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->defaultSort('created_at', 'desc')
      ->filters([
        SelectFilter::make('customer_id')
          ->label('Client payeur')
          ->options(fn (): array => User::query()
            ->whereHas('orders', fn (Builder $orders): Builder => $orders->whereHas('payment'))
            ->orderBy('full_name')
            ->pluck('full_name', 'id')
            ->all())
          ->searchable()
          ->preload()
          ->query(function (Builder $query, array $data): Builder {
            if (blank($data['value'] ?? null)) {
              return $query;
            }

            return $query->whereHas('order', fn (Builder $order): Builder => $order->where(
              'user_id',
              $data['value'],
            ));
          }),
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
        SelectFilter::make('books_received')
          ->label('Livre reçu')
          ->options([
            'yes' => 'Reçu',
            'no' => 'Non reçu',
            'na' => 'Numérique uniquement',
          ])
          ->query(function (Builder $query, array $data): Builder {
            if (blank($data['value'] ?? null)) {
              return $query;
            }

            return $query->whereHas(
              'order',
              fn (Builder $order): Builder => OrderBooksReceivedQuery::applyFilter($order, $data['value']),
            );
          }),
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
