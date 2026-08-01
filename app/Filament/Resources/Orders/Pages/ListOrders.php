<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\Orders\OrderAdminExportService;
use App\Support\ExportDownloadResponse;
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
        ->badge(fn (): int => Order::query()->count()),
      'shop' => Tab::make('Site (boutique)')
        ->icon(Heroicon::OutlinedShoppingBag)
        ->badge(fn (): int => Order::query()->where('source', OrderSource::Shop)->count())
        ->modifyQueryUsing(fn (Builder $query): Builder => $query->where(
          'source',
          OrderSource::Shop->value,
        )),
      'direct_payment' => Tab::make('Vente directe')
        ->icon(Heroicon::OutlinedQrCode)
        ->badge(fn (): int => Order::query()->where('source', OrderSource::DirectPayment)->count())
        ->modifyQueryUsing(fn (Builder $query): Builder => $query->where(
          'source',
          OrderSource::DirectPayment->value,
        )),
      'pending_payment' => Tab::make('En attente paiement')
        ->icon(Heroicon::OutlinedClock)
        ->badge(fn (): int => Order::query()->where('status', OrderStatus::PendingPayment)->count())
        ->badgeColor('warning')
        ->modifyQueryUsing(fn (Builder $query): Builder => $query->where(
          'status',
          OrderStatus::PendingPayment->value,
        )),
      'paid' => Tab::make('Payées')
        ->icon(Heroicon::OutlinedCheckBadge)
        ->badge(fn (): int => Order::query()->whereNotNull('paid_at')->count())
        ->badgeColor('success')
        ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNotNull('paid_at')),
      'awaiting_handover' => Tab::make('À remettre')
        ->icon(Heroicon::OutlinedTruck)
        ->badge(fn (): int => Order::query()
          ->whereNotNull('paid_at')
          ->whereIn('status', [
            OrderStatus::Paid->value,
            OrderStatus::Processing->value,
            OrderStatus::OutForDelivery->value,
            OrderStatus::DeliveredByCourier->value,
          ])
          ->count())
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
   * Actions d'en-tête : exports Excel et PDF des commandes filtrées.
   *
   * @return array<int, Action> Actions disponibles
   */
  protected function getHeaderActions(): array
  {
    return [
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
