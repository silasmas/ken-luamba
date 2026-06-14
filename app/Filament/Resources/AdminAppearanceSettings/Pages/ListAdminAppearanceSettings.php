<?php

namespace App\Filament\Resources\AdminAppearanceSettings\Pages;

use App\Filament\Resources\AdminAppearanceSettings\AdminAppearanceSettingResource;
use App\Models\AdminAppearanceSetting;
use Filament\Resources\Pages\ListRecords;

/**
 * Redirige vers la page unique des paramètres d'apparence.
 */
class ListAdminAppearanceSettings extends ListRecords
{
  protected static string $resource = AdminAppearanceSettingResource::class;

  /**
   * Redirige directement vers l'édition du singleton.
   *
   * @return void
   */
  public function mount(): void
  {
    $record = AdminAppearanceSetting::instance();

    $this->redirect(AdminAppearanceSettingResource::getUrl('edit', ['record' => $record]));
  }
}
