<?php

namespace App\Filament\Resources\ContactSettings\Pages;

use App\Filament\Resources\ContactSettings\ContactSettingResource;
use App\Models\ContactSetting;
use Filament\Resources\Pages\ListRecords;

/**
 * Redirige vers la page unique des paramètres de contact.
 */
class ListContactSettings extends ListRecords
{
  protected static string $resource = ContactSettingResource::class;

  /**
   * Redirige directement vers l'édition du singleton.
   *
   * @return void
   */
  public function mount(): void
  {
    $record = ContactSetting::instance();

    $this->redirect(ContactSettingResource::getUrl('edit', ['record' => $record]));
  }
}
