<?php

namespace App\Filament\Resources\ShippingSettings\Pages;

use App\Filament\Resources\ShippingSettings\ShippingSettingResource;
use App\Models\ShopSetting;
use Filament\Resources\Pages\EditRecord;

/**
 * Page d'édition des paramètres globaux de livraison.
 */
class ManageShippingSettings extends EditRecord
{
  protected static string $resource = ShippingSettingResource::class;

  protected static ?string $title = 'Paramètres de livraison';

  /**
   * Force la devise boutique sur les paramètres de livraison.
   *
   * @param array<string, mixed> $data Données du formulaire
   * @return array<string, mixed> Données enregistrées
   */
  protected function mutateFormDataBeforeSave(array $data): array
  {
    $data['currency'] = ShopSetting::currencyCode();

    return $data;
  }

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
