<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Services\Payments\PaymentAdminExportService;
use App\Support\ExportDownloadResponse;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListPayments extends ListRecords
{
  protected static string $resource = PaymentResource::class;

  /**
   * Actions d'en-tête : exports Excel et PDF des paiements filtrés.
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
          $payments = $this->getFilteredTableQuery()
            ->with(['order.user', 'order.items', 'order.delivery'])
            ->get();

          if ($payments->isEmpty()) {
            Notification::make()
              ->title('Aucun paiement à exporter')
              ->warning()
              ->send();

            $this->halt();
          }

          $path = app(PaymentAdminExportService::class)->exportExcel($payments);

          return ExportDownloadResponse::stream($path);
        }),
      Action::make('exportPdf')
        ->label('Exporter PDF')
        ->icon(Heroicon::OutlinedDocumentArrowDown)
        ->color('gray')
        ->action(function (): StreamedResponse {
          $payments = $this->getFilteredTableQuery()
            ->with(['order.user', 'order.items', 'order.delivery'])
            ->get();

          if ($payments->isEmpty()) {
            Notification::make()
              ->title('Aucun paiement à exporter')
              ->warning()
              ->send();

            $this->halt();
          }

          $path = app(PaymentAdminExportService::class)->exportPdf($payments);

          return ExportDownloadResponse::stream($path);
        }),
    ];
  }
}
