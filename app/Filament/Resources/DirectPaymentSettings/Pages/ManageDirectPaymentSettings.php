<?php

namespace App\Filament\Resources\DirectPaymentSettings\Pages;

use App\Filament\Resources\DirectPaymentSettings\DirectPaymentSettingResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Page d'édition des paramètres paiement direct.
 */
class ManageDirectPaymentSettings extends EditRecord
{
  protected static string $resource = DirectPaymentSettingResource::class;

  protected static ?string $title = 'Paiement direct';

  /**
   * Empêche la suppression des paramètres globaux.
   *
   * @return array<int, mixed> Actions vides
   */
  protected function getHeaderActions(): array
  {
    return [];
  }
}
