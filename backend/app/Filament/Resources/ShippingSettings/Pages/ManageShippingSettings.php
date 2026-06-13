<?php

namespace App\Filament\Resources\ShippingSettings\Pages;

use App\Filament\Resources\ShippingSettings\ShippingSettingResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Page d'édition des paramètres globaux de livraison.
 */
class ManageShippingSettings extends EditRecord
{
  protected static string $resource = ShippingSettingResource::class;

  protected static ?string $title = 'Paramètres de livraison';

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
