<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Services\CourierGateService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

/**
 * Page d'édition utilisateur avec génération optionnelle du code livreur.
 */
class EditUser extends EditRecord
{
  protected static string $resource = UserResource::class;

  /**
   * Actions d'en-tête.
   *
   * @return array<int, mixed>
   */
  protected function getHeaderActions(): array
  {
    return [
      DeleteAction::make(),
    ];
  }

  /**
   * Après sauvegarde : génère le code livreur si demandé.
   */
  protected function afterSave(): void
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
