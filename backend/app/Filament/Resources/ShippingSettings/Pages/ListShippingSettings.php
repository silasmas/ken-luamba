<?php

namespace App\Filament\Resources\ShippingSettings\Pages;

use App\Filament\Resources\ShippingSettings\ShippingSettingResource;
use App\Models\ShippingSetting;
use Filament\Resources\Pages\ListRecords;

/**
 * Redirige vers la page unique des paramètres de livraison.
 */
class ListShippingSettings extends ListRecords
{
  protected static string $resource = ShippingSettingResource::class;

  /**
   * Redirige directement vers l'édition du singleton.
   *
   * @return void
   */
  public function mount(): void
  {
    $record = ShippingSetting::instance();

    $this->redirect(ShippingSettingResource::getUrl('edit', ['record' => $record]));
  }
}
