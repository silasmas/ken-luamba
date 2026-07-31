<?php

namespace App\Filament\Resources\DirectPaymentSettings\Pages;

use App\Filament\Resources\DirectPaymentSettings\DirectPaymentSettingResource;
use App\Services\DirectPaymentQrService;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

/**
 * Page d'édition des paramètres paiement direct.
 */
class ManageDirectPaymentSettings extends EditRecord
{
  protected static string $resource = DirectPaymentSettingResource::class;

  protected static ?string $title = 'Paiement direct';

  /**
   * Actions d'en-tête (téléchargement QR).
   *
   * @return array<int, mixed>
   */
  protected function getHeaderActions(): array
  {
    return [
      Action::make('downloadQr')
        ->label('Télécharger le QR')
        ->icon('heroicon-o-qr-code')
        ->url(fn (): string => app(DirectPaymentQrService::class)->imageUrl().'?size=800&download=1')
        ->openUrlInNewTab(),
    ];
  }
}
