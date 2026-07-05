<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Models\User;
use App\Services\DeliveryService;
use App\Filament\Support\OrderBooksReceivedAdminAction;
use App\Support\OrderAdminFormatter;
use App\Support\OrderBooksReceivedQuery;
use App\Support\OrderExtraContributionQuery;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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
          ->extraCellAttributes(['class' => 'align-top'])
          ->tooltip(fn ($record): ?string => OrderAdminFormatter::itemsSummary($record) !== '—'
            ? OrderAdminFormatter::itemsSummary($record)
            : null)
          ->searchable(query: function ($query, string $search): void {
            $query->whereHas('items', fn ($items) => $items->where('book_title', 'like', "%{$search}%"));
          }),
        TextColumn::make('payment_mode')
          ->label('Mode d\'achat')
          ->state(fn ($record): string => OrderAdminFormatter::paymentModeLabel($record))
          ->description(fn ($record): ?string => OrderAdminFormatter::paymentModeDescription($record))
          ->badge()
          ->color(fn ($record): string => OrderAdminFormatter::hasExtraContribution($record) ? 'success' : 'gray')
          ->toggleable(),
        TextColumn::make('extra_contribution_amount')
          ->label('Soutien +')
          ->money(fn ($record) => $record->currency)
          ->sortable()
          ->placeholder('—')
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('books_received')
          ->label('Livre reçu')
          ->state(fn ($record): string => OrderAdminFormatter::booksReceivedLabel($record))
          ->description(fn ($record): ?string => OrderAdminFormatter::booksPendingSummary($record))
          ->badge()
          ->color(fn ($record): string => match (true) {
            OrderAdminFormatter::isBooksReceived($record) => 'success',
            OrderAdminFormatter::isBooksPartiallyReceived($record) => 'info',
            ! $record->isDigitalOnly() => 'warning',
            default => 'gray',
          })
          ->toggleable(),
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
        SelectFilter::make('user_id')
          ->label('Client')
          ->options(fn (): array => User::query()
            ->whereHas('orders')
            ->orderBy('full_name')
            ->pluck('full_name', 'id')
            ->all())
          ->searchable()
          ->preload(),
        SelectFilter::make('status')
          ->label('Statut')
          ->options(collect(OrderStatus::cases())->mapWithKeys(
            fn (OrderStatus $status) => [$status->value => $status->label()]
          )->all()),
        SelectFilter::make('payment_state')
          ->label('Paiement')
          ->options([
            'paid' => 'Payée',
            'unpaid' => 'Non payée',
          ])
          ->query(function (Builder $query, array $data): Builder {
            return match ($data['value'] ?? null) {
              'paid' => $query->whereNotNull('paid_at'),
              'unpaid' => $query->whereNull('paid_at'),
              default => $query,
            };
          }),
        SelectFilter::make('books_received')
          ->label('Livre reçu')
          ->options([
            'yes' => 'Reçu',
            'partial' => 'Partiel',
            'no' => 'Non reçu',
            'na' => 'Numérique uniquement',
          ])
          ->query(fn (Builder $query, array $data): Builder => OrderBooksReceivedQuery::applyFilter(
            $query,
            $data['value'] ?? null,
          )),
        SelectFilter::make('payment_mode')
          ->label('Mode d\'achat')
          ->options([
            'normal' => 'Prix normal',
            'voluntary' => 'Prix volontaire',
          ])
          ->query(fn (Builder $query, array $data): Builder => OrderExtraContributionQuery::applyFilter(
            $query,
            $data['value'] ?? null,
          )),
      ])
      ->recordActions([
        OrderBooksReceivedAdminAction::manageReceipt(),
        Action::make('markBooksNotReceived')
          ->label('Tout remettre en attente')
          ->icon(Heroicon::OutlinedXCircle)
          ->color('warning')
          ->requiresConfirmation()
          ->modalDescription('Tous les articles seront marqués comme non reçus et la commande repassera en attente de remise.')
          ->visible(fn ($record): bool => ! $record->isDigitalOnly()
            && (OrderAdminFormatter::isBooksReceived($record)
              || OrderAdminFormatter::isBooksPartiallyReceived($record)))
          ->action(function ($record): void {
            app(DeliveryService::class)->markBooksNotReceivedByAdmin($record);

            Notification::make()
              ->title('Réception réinitialisée')
              ->success()
              ->send();
          }),
        EditAction::make(),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          BulkAction::make('markBooksReceivedBulk')
            ->label('Marquer tout reçu')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->action(function (Collection $records): void {
              $service = app(DeliveryService::class);
              $count = 0;

              foreach ($records as $record) {
                if ($record->isDigitalOnly() || OrderAdminFormatter::isBooksReceived($record)) {
                  continue;
                }

                $physicalItemIds = OrderAdminFormatter::physicalItems($record)->pluck('id')->all();
                $service->syncPhysicalItemsReceiptByAdmin($record, $physicalItemIds);
                $count++;
              }

              Notification::make()
                ->title("{$count} commande(s) marquée(s) comme entièrement reçue(s)")
                ->success()
                ->send();
            }),
          DeleteBulkAction::make(),
        ]),
      ]);
  }
}
