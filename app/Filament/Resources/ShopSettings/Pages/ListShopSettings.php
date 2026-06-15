<?php

namespace App\Filament\Resources\ShopSettings\Pages;

use App\Filament\Resources\ShopSettings\ShopSettingResource;
use App\Models\ShopSetting;
use Filament\Resources\Pages\ListRecords;

/**
 * Redirige vers la page unique des paramètres boutique.
 */
class ListShopSettings extends ListRecords
{
  protected static string $resource = ShopSettingResource::class;

  /**
   * Redirige directement vers l'édition du singleton.
   *
   * @return void
   */
  public function mount(): void
  {
    $record = ShopSetting::instance();

    $this->redirect(ShopSettingResource::getUrl('edit', ['record' => $record]));
  }
}
