<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Widgets\MailQuotaWidget;
use App\Filament\Widgets\OrderListStatsWidget;
use App\Models\Order;
use App\Models\ShopSetting;
use App\Services\Mail\MailQuotaService;
use App\Services\OrderNotificationService;
use App\Services\Orders\OrderAdminExportService;
use App\Services\Orders\OrderListStatsService;
use App\Support\ExportDownloadResponse;
use App\Support\OrderBooksReceivedQuery;
use App\Support\OrderPaymentVerification;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Liste des commandes avec onglets canal / statut et exports.
 */
class ListOrders extends ListRecords
{
  protected static string $resource = OrderResource::class;

  /**
   * Onglets de regroupement : toutes, boutique, vente directe, en attente, payées.
   *
   * @return array<string, Tab>
   */
  public function getTabs(): array
  {
    return [
      'all' => Tab::make('Toutes')
        ->badge(fn (): string => $this->tabBadge(Order::query(), includeRevenue: true))
        ->badgeColor('primary'),
      'shop' => Tab::make('Site (boutique)')
        ->icon(Heroicon::OutlinedShoppingBag)
        ->badge(fn (): string => $this->tabBadge(
          Order::query()->where('source', OrderSource::Shop->value),
          includeRevenue: true,
        ))
        ->badgeColor('info')
        ->modifyQueryUsing(fn (Builder $query): Builder => $query->where(
          'source',
          OrderSource::Shop->value,
        )),
      'direct_payment' => Tab::make('Vente directe')
        ->icon(Heroicon::OutlinedQrCode)
        ->badge(fn (): string => $this->tabBadge(
          Order::query()->where('source', OrderSource::DirectPayment->value),
          includeRevenue: true,
        ))
        ->badgeColor('warning')
        ->modifyQueryUsing(fn (Builder $query): Builder => $query->where(
          'source',
          OrderSource::DirectPayment->value,
        )),
      'direct_to_collect' => Tab::make('Direct — à récupérer')
        ->icon(Heroicon::OutlinedHandRaised)
        ->badge(fn (): string => $this->tabBadge(
          OrderBooksReceivedQuery::notReceived(
            Order::query()
              ->where('source', OrderSource::DirectPayment->value)
              ->whereNotNull('paid_at'),
          ),
          includeRevenue: true,
        ))
        ->badgeColor('warning')
        ->modifyQueryUsing(fn (Builder $query): Builder => OrderBooksReceivedQuery::notReceived(
          $query
            ->where('source', OrderSource::DirectPayment->value)
            ->whereNotNull('paid_at'),
        )),
      'direct_collected' => Tab::make('Direct — récupéré')
        ->icon(Heroicon::OutlinedCheckBadge)
        ->badge(fn (): string => $this->tabBadge(
          OrderBooksReceivedQuery::received(
            Order::query()
              ->where('source', OrderSource::DirectPayment->value)
              ->whereNotNull('paid_at'),
          ),
          includeRevenue: true,
        ))
        ->badgeColor('success')
        ->modifyQueryUsing(fn (Builder $query): Builder => OrderBooksReceivedQuery::received(
          $query
            ->where('source', OrderSource::DirectPayment->value)
            ->whereNotNull('paid_at'),
        )),
      'pending_payment' => Tab::make('En attente paiement')
        ->icon(Heroicon::OutlinedClock)
        ->badge(fn (): string => $this->tabBadge(
          Order::query()->where('status', OrderStatus::PendingPayment->value),
          includeRevenue: false,
        ))
        ->badgeColor('warning')
        ->modifyQueryUsing(fn (Builder $query): Builder => $query->where(
          'status',
          OrderStatus::PendingPayment->value,
        )),
      'paid' => Tab::make('Payées')
        ->icon(Heroicon::OutlinedCheckBadge)
        ->badge(fn (): string => $this->tabBadge(
          Order::query()->whereNotNull('paid_at'),
          includeRevenue: true,
        ))
        ->badgeColor('success')
        ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNotNull('paid_at')),
      'awaiting_handover' => Tab::make('À remettre')
        ->icon(Heroicon::OutlinedTruck)
        ->badge(fn (): string => $this->tabBadge(
          Order::query()
            ->whereNotNull('paid_at')
            ->whereIn('status', [
              OrderStatus::Paid->value,
              OrderStatus::Processing->value,
              OrderStatus::OutForDelivery->value,
              OrderStatus::DeliveredByCourier->value,
            ]),
          includeRevenue: true,
        ))
        ->badgeColor('warning')
        ->modifyQueryUsing(fn (Builder $query): Builder => $query
          ->whereNotNull('paid_at')
          ->whereIn('status', [
            OrderStatus::Paid->value,
            OrderStatus::Processing->value,
            OrderStatus::OutForDelivery->value,
            OrderStatus::DeliveredByCourier->value,
          ])),
    ];
  }

  /**
   * Badge d'onglet : nombre de commandes (+ total encaissé si pertinent).
   *
   * @param Builder $query Requête de l'onglet
   * @param bool $includeRevenue Inclure le CA payé dans le badge
   * @return string Libellé badge
   */
  private function tabBadge(Builder $query, bool $includeRevenue = false): string
  {
    $count = (clone $query)->count();

    if (! $includeRevenue || $count === 0) {
      return (string) $count;
    }

    $revenue = (float) ((clone $query)->whereNotNull('paid_at')->sum('total') ?? 0);
    $service = app(OrderListStatsService::class);

    return $count.' · '.$service->formatMoney($revenue, ShopSetting::currencyCode());
  }

  /**
   * Widgets au-dessus de la liste commandes.
   *
   * @return array<int, class-string>
   */
  protected function getHeaderWidgets(): array
  {
    return [
      MailQuotaWidget::class,
      OrderListStatsWidget::class,
    ];
  }

  /**
   * Actions d'en-tête : vérif/renvoi groupés + exports.
   *
   * @return array<int, Action> Actions disponibles
   */
  protected function getHeaderActions(): array
  {
    $quota = app(MailQuotaService::class)->snapshot();

    return [
      Action::make('mailQuotaStatus')
        ->label($quota['label'])
        ->icon(Heroicon::OutlinedEnvelope)
        ->color($quota['color'])
        ->disabled()
        ->extraAttributes([
          'title' => sprintf(
            'Envoyés app : %d · Restants estimés : %d · Plafond : %d / 24 h',
            $quota['used'],
            $quota['remaining'],
            $quota['limit'],
          ),
        ]),
      Action::make('verifyAllPendingInFilter')
        ->label('Vérifier les en attente')
        ->icon(Heroicon::OutlinedArrowPath)
        ->color('warning')
        ->requiresConfirmation()
        ->modalHeading('Vérifier tous les paiements en attente')
        ->modalDescription('Interroge FlexPay pour toutes les commandes en attente du filtre / onglet actuel.')
        ->action(function (): void {
          $orders = $this->getFilteredTableQuery()
            ->with(['payment', 'user'])
            ->get()
            ->filter(fn (Order $order): bool => OrderPaymentVerification::canVerify($order));

          if ($orders->isEmpty()) {
            Notification::make()
              ->title('Aucune commande à vérifier')
              ->body('Aucune commande en attente dans le filtre actuel.')
              ->warning()
              ->send();

            return;
          }

          $confirmed = 0;
          $pending = 0;
          $failed = 0;

          foreach ($orders as $order) {
            $result = OrderPaymentVerification::verify($order);

            match ($result['color']) {
              'success' => $confirmed++,
              'danger' => $failed++,
              default => $pending++,
            };
          }

          Notification::make()
            ->title('Vérification groupée terminée')
            ->body("Traitées : {$orders->count()} · Confirmés : {$confirmed} · En attente : {$pending} · Échoués : {$failed}")
            ->success()
            ->send();
        }),
      Action::make('resendAllPaidEmailsInFilter')
        ->label('Renvoyer mails achat')
        ->icon(Heroicon::OutlinedEnvelope)
        ->color('info')
        ->requiresConfirmation()
        ->modalHeading('Renvoyer les mails d\'achat')
        ->modalDescription('Maximum 30 mails du filtre actuel, espacés de 8s pour respecter le quota Hostinger (~1000/24 h).')
        ->action(function (): void {
          $orders = $this->getFilteredTableQuery()
            ->with('user')
            ->whereNotNull('paid_at')
            ->limit(30)
            ->get()
            ->filter(fn (Order $order): bool => filled($order->user?->email))
            ->values();

          if ($orders->isEmpty()) {
            Notification::make()
              ->title('Aucun mail à renvoyer')
              ->body('Aucune commande payée avec email dans le filtre actuel.')
              ->warning()
              ->send();

            return;
          }

          $service = app(OrderNotificationService::class);
          $sent = 0;
          $failed = 0;

          foreach ($orders as $index => $order) {
            $result = $service->resendPaymentSuccessEmail($order, $index * 8);
            $result['success'] ? $sent++ : $failed++;
          }

          Notification::make()
            ->title('Renvoi groupé mis en file')
            ->body("En file : {$sent} · Échoués : {$failed} (max 30/lot, worker queue requis)")
            ->success()
            ->send();
        }),
      Action::make('exportExcel')
        ->label('Exporter Excel')
        ->icon(Heroicon::OutlinedArrowDownTray)
        ->color('success')
        ->action(function (): StreamedResponse {
          $orders = $this->getFilteredTableQuery()
            ->with(['user', 'items', 'delivery', 'payment'])
            ->get();

          if ($orders->isEmpty()) {
            Notification::make()
              ->title('Aucune commande à exporter')
              ->warning()
              ->send();

            $this->halt();
          }

          $path = app(OrderAdminExportService::class)->exportExcel($orders);

          return ExportDownloadResponse::stream($path);
        }),
      Action::make('exportPdf')
        ->label('Exporter PDF')
        ->icon(Heroicon::OutlinedDocumentArrowDown)
        ->color('gray')
        ->action(function (): StreamedResponse {
          $orders = $this->getFilteredTableQuery()
            ->with(['user', 'items', 'delivery', 'payment'])
            ->get();

          if ($orders->isEmpty()) {
            Notification::make()
              ->title('Aucune commande à exporter')
              ->warning()
              ->send();

            $this->halt();
          }

          $path = app(OrderAdminExportService::class)->exportPdf($orders);

          return ExportDownloadResponse::stream($path);
        }),
    ];
  }
}
