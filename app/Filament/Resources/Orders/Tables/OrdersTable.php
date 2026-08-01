<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\FulfillmentType;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Models\User;
use App\Services\DeliveryService;
use App\Services\OrderNotificationService;
use App\Filament\Support\OrderBooksReceivedAdminAction;
use App\Filament\Support\ResizableTableColumn;
use App\Support\OrderAdminFormatter;
use App\Support\OrderBooksReceivedQuery;
use App\Support\OrderExtraContributionQuery;
use App\Support\OrderPaymentVerification;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
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
          ->extraHeaderAttributes(ResizableTableColumn::attributes('items_summary', '26rem')['header'])
          ->extraCellAttributes(ResizableTableColumn::attributes('items_summary', '26rem')['cell'])
          ->tooltip(fn ($record): ?string => OrderAdminFormatter::itemsSummary($record) !== '—'
            ? OrderAdminFormatter::itemsSummary($record)
            : null)
          ->searchable(query: function ($query, string $search): void {
            $query->whereHas('items', fn ($items) => $items->where('book_title', 'like', "%{$search}%"));
          }),
        TextColumn::make('source')
          ->label('Canal')
          ->state(fn ($record): string => OrderAdminFormatter::salesChannelLabel($record))
          ->badge()
          ->color(fn ($record): string => OrderAdminFormatter::salesChannelColor($record))
          ->sortable()
          ->toggleable(),
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
          ->state(fn ($record) => OrderAdminFormatter::booksReceivedCellHtml($record))
          ->html()
          ->extraHeaderAttributes(ResizableTableColumn::attributes('books_received', '16rem')['header'])
          ->extraCellAttributes(ResizableTableColumn::attributes('books_received', '16rem')['cell'])
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
        TextColumn::make('payment.channel')
          ->label('Paiement')
          ->formatStateUsing(fn ($state): string => $state instanceof PaymentChannel
            ? $state->label()
            : '—')
          ->badge()
          ->color(fn ($state): string => match ($state) {
            PaymentChannel::MobileMoney => 'warning',
            PaymentChannel::Card => 'info',
            default => 'gray',
          })
          ->toggleable(),
        TextColumn::make('purchase_email')
          ->label('Mail achat')
          ->state(fn ($record): string => OrderAdminFormatter::purchaseEmailLabel($record))
          ->description(fn ($record): ?string => OrderAdminFormatter::purchaseEmailDescription($record))
          ->badge()
          ->color(fn ($record): string => OrderAdminFormatter::purchaseEmailColor($record))
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
        TextColumn::make('created_at')
          ->label('Créée le')
          ->formatStateUsing(fn ($state): string => OrderAdminFormatter::formatLocalizedDateTime($state))
          ->sortable()
          ->toggleable(),
        TextColumn::make('paid_at')
          ->label('Payée le')
          ->formatStateUsing(fn ($state): string => OrderAdminFormatter::formatLocalizedDateTime($state))
          ->sortable()
          ->toggleable(),
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
        SelectFilter::make('source')
          ->label('Canal')
          ->options(collect(OrderSource::cases())->mapWithKeys(
            fn (OrderSource $source): array => [$source->value => $source->label()]
          )->all()),
        SelectFilter::make('status')
          ->label('Statut commande')
          ->options(collect(OrderStatus::cases())->mapWithKeys(
            fn (OrderStatus $status) => [$status->value => $status->label()]
          )->all())
          ->multiple(),
        SelectFilter::make('payment_state')
          ->label('État paiement')
          ->options([
            'paid' => 'Payée',
            'unpaid' => 'Non payée',
            'pending_gateway' => 'En attente FlexPay',
          ])
          ->query(function (Builder $query, array $data): Builder {
            return match ($data['value'] ?? null) {
              'paid' => $query->whereNotNull('paid_at'),
              'unpaid' => $query->whereNull('paid_at'),
              'pending_gateway' => $query
                ->where('status', OrderStatus::PendingPayment)
                ->whereHas('payment', fn (Builder $payment): Builder => $payment->whereIn('status', [
                  PaymentStatus::Pending->value,
                  PaymentStatus::Processing->value,
                ])),
              default => $query,
            };
          }),
        SelectFilter::make('payment_channel')
          ->label('Moyen de paiement')
          ->options(collect(PaymentChannel::cases())->mapWithKeys(
            fn (PaymentChannel $channel): array => [$channel->value => $channel->label()]
          )->all())
          ->query(function (Builder $query, array $data): Builder {
            $value = $data['value'] ?? null;

            if (! filled($value)) {
              return $query;
            }

            return $query->whereHas(
              'payment',
              fn (Builder $payment): Builder => $payment->where('channel', $value),
            );
          }),
        SelectFilter::make('payment_status')
          ->label('Statut transaction')
          ->options(collect(PaymentStatus::cases())->mapWithKeys(
            fn (PaymentStatus $status): array => [$status->value => $status->label()]
          )->all())
          ->query(function (Builder $query, array $data): Builder {
            $value = $data['value'] ?? null;

            if (! filled($value)) {
              return $query;
            }

            return $query->whereHas(
              'payment',
              fn (Builder $payment): Builder => $payment->where('status', $value),
            );
          }),
        SelectFilter::make('fulfillment_type')
          ->label('Réception')
          ->options(collect(FulfillmentType::cases())->mapWithKeys(
            fn (FulfillmentType $type): array => [$type->value => $type->label()]
          )->all()),
        SelectFilter::make('purchase_email')
          ->label('Mail achat')
          ->options([
            'sent' => 'Envoyé',
            'not_sent' => 'Non envoyé (payée)',
          ])
          ->query(function (Builder $query, array $data): Builder {
            return match ($data['value'] ?? null) {
              'sent' => $query->whereNotNull('payment_success_email_sent_at'),
              'not_sent' => $query
                ->whereNotNull('paid_at')
                ->whereNull('payment_success_email_sent_at'),
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
        Filter::make('created_between')
          ->label('Date de création')
          ->schema([
            DatePicker::make('from')
              ->label('Du')
              ->native(false),
            DatePicker::make('until')
              ->label('Au')
              ->native(false),
          ])
          ->query(function (Builder $query, array $data): Builder {
            return $query
              ->when(
                filled($data['from'] ?? null),
                fn (Builder $q): Builder => $q->whereDate('created_at', '>=', $data['from']),
              )
              ->when(
                filled($data['until'] ?? null),
                fn (Builder $q): Builder => $q->whereDate('created_at', '<=', $data['until']),
              );
          }),
      ])
      ->filtersFormColumns(2)
      ->recordActions([
        Action::make('verifyPayment')
          ->label('Vérifier paiement')
          ->icon(Heroicon::OutlinedArrowPath)
          ->color('warning')
          ->requiresConfirmation()
          ->modalHeading('Vérifier la transaction FlexPay')
          ->modalDescription('Interroge FlexPay pour savoir si le paiement en attente a abouti.')
          ->visible(fn ($record): bool => OrderPaymentVerification::canVerify($record))
          ->action(function ($record): void {
            $result = OrderPaymentVerification::verify($record);

            Notification::make()
              ->title($result['title'])
              ->body($result['message'])
              ->{$result['color']}()
              ->send();
          }),
        Action::make('resendPurchaseEmail')
          ->label('Renvoyer mail achat')
          ->icon(Heroicon::OutlinedEnvelope)
          ->color('info')
          ->requiresConfirmation()
          ->modalHeading('Renvoyer le mail de confirmation')
          ->modalDescription(fn ($record): string => 'Un email de confirmation sera envoyé à '
            .($record->user?->email ?? 'l\'adresse du client').'.')
          ->visible(fn ($record): bool => $record->paid_at !== null && filled($record->user?->email))
          ->action(function ($record): void {
            $result = app(OrderNotificationService::class)->resendPaymentSuccessEmail($record);

            Notification::make()
              ->title($result['success'] ? 'Mail renvoyé' : 'Envoi impossible')
              ->body($result['message'])
              ->{$result['success'] ? 'success' : 'danger'}()
              ->send();
          }),
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
        BulkAction::make('verifyPaymentsBulk')
          ->label('Vérifier paiements')
          ->icon(Heroicon::OutlinedArrowPath)
          ->color('warning')
          ->button()
          ->requiresConfirmation()
          ->modalHeading('Vérifier les paiements sélectionnés')
          ->modalDescription('Interroge FlexPay pour chaque commande sélectionnée encore en attente.')
          ->deselectRecordsAfterCompletion()
          ->action(function (Collection $records): void {
            $records->loadMissing(['payment', 'user']);
            $confirmed = 0;
            $pending = 0;
            $failed = 0;
            $skipped = 0;

            foreach ($records as $record) {
              if (! OrderPaymentVerification::canVerify($record)) {
                $skipped++;
                continue;
              }

              $result = OrderPaymentVerification::verify($record);

              match ($result['color']) {
                'success' => $confirmed++,
                'danger' => $failed++,
                default => $pending++,
              };
            }

            Notification::make()
              ->title('Vérification groupée terminée')
              ->body("Confirmés : {$confirmed} · En attente : {$pending} · Échoués : {$failed} · Ignorés : {$skipped}")
              ->success()
              ->send();
          }),
        BulkAction::make('resendPurchaseEmailsBulk')
          ->label('Renvoyer mails achat')
          ->icon(Heroicon::OutlinedEnvelope)
          ->color('info')
          ->button()
          ->requiresConfirmation()
          ->modalHeading('Renvoyer les mails d\'achat')
          ->modalDescription('Envoie le mail de confirmation à chaque client des commandes payées sélectionnées.')
          ->deselectRecordsAfterCompletion()
          ->action(function (Collection $records): void {
            $records->loadMissing('user');
            $service = app(OrderNotificationService::class);
            $sent = 0;
            $failed = 0;
            $skipped = 0;

            foreach ($records as $record) {
              if ($record->paid_at === null || blank($record->user?->email)) {
                $skipped++;
                continue;
              }

              $result = $service->resendPaymentSuccessEmail($record);
              $result['success'] ? $sent++ : $failed++;
            }

            Notification::make()
              ->title('Renvoi groupé terminé')
              ->body("Envoyés : {$sent} · Échoués : {$failed} · Ignorés : {$skipped}")
              ->success()
              ->send();
          }),
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
