<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Services\CourierGateService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

/**
 * Page de création utilisateur avec génération optionnelle du code livreur.
 */
class CreateUser extends CreateRecord
{
  protected static string $resource = UserResource::class;

  /**
   * Après création : génère le code livreur si demandé.
   */
  protected function afterCreate(): void
  {
    if ($this->record->role !== UserRole::Courier) {
      return;
    }

    if (! (bool) ($this->data['regenerate_courier_code'] ?? false)) {
      return;
    }

    $plain = CourierGateService::generateAndStoreCode($this->record);

    Notification::make()
      ->title('Code livreur généré')
      ->body('Communiquez ce code au livreur (affiché une seule fois) : '.$plain)
      ->success()
      ->persistent()
      ->send();
  }
}
