<?php

namespace App\Filament\Resources\DirectPaymentSettings\Pages;

use App\Filament\Resources\DirectPaymentSettings\DirectPaymentSettingResource;
use App\Models\DirectPaymentSetting;
use Filament\Resources\Pages\ListRecords;

/**
 * Redirige vers la page unique des paramètres paiement direct.
 */
class ListDirectPaymentSettings extends ListRecords
{
  protected static string $resource = DirectPaymentSettingResource::class;

  /**
   * Redirige directement vers l'édition du singleton.
   */
  public function mount(): void
  {
    $record = DirectPaymentSetting::instance();

    $this->redirect(DirectPaymentSettingResource::getUrl('edit', ['record' => $record]));
  }
}
