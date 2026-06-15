<?php

namespace App\Filament\Resources\ContactSettings\Pages;

use App\Filament\Resources\ContactSettings\ContactSettingResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Page d'édition des paramètres de contact publics.
 */
class ManageContactSettings extends EditRecord
{
  protected static string $resource = ContactSettingResource::class;

  protected static ?string $title = 'Page contact';

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
