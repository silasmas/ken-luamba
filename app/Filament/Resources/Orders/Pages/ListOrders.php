<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Services\Orders\OrderAdminExportService;
use App\Support\ExportDownloadResponse;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListOrders extends ListRecords
{
  protected static string $resource = OrderResource::class;

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
