<?php

namespace App\Filament\Resources\ShopSettings\Pages;

use App\Filament\Resources\ShopSettings\ShopSettingResource;
use App\Models\ShippingSetting;
use App\Models\ShippingZone;
use Filament\Resources\Pages\EditRecord;

/**
 * Page d'édition des paramètres globaux de la boutique.
 */
class ManageShopSettings extends EditRecord
{
  protected static string $resource = ShopSettingResource::class;

  protected static ?string $title = 'Paramètres boutique';

  /**
   * Empêche la suppression des paramètres globaux.
   *
   * @return array<int, mixed> Actions vides
   */
  protected function getHeaderActions(): array
  {
    return [];
  }

  /**
   * Aligne la devise des frais de livraison sur la devise boutique.
   *
   * @param array<string, mixed> $data Données du formulaire
   * @return array<string, mixed> Données enregistrées
   */
  protected function mutateFormDataBeforeSave(array $data): array
  {
    $currency = (string) ($data['currency'] ?? 'CDF');

    ShippingSetting::query()->update(['currency' => $currency]);
    ShippingZone::query()->update(['currency' => $currency]);

    return $data;
  }
}
