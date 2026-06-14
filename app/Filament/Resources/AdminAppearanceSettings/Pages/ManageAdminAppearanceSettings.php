<?php

namespace App\Filament\Resources\AdminAppearanceSettings\Pages;

use App\Filament\Resources\AdminAppearanceSettings\AdminAppearanceSettingResource;
use Filament\Resources\Pages\EditRecord;

/**
 * Page d'édition des paramètres d'apparence de l'administration.
 */
class ManageAdminAppearanceSettings extends EditRecord
{
  protected static string $resource = AdminAppearanceSettingResource::class;

  protected static ?string $title = 'Apparence de l\'administration';

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
