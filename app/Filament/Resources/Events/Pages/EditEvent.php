<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Filament\Support\InvitationAdminActions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
  protected static string $resource = EventResource::class;

  /**
   * Persiste les relations du formulaire (ex. livres associés) après la mise à jour.
   *
   * Filament 5 n'appelle pas saveRelationships() automatiquement sur EditRecord.
   *
   * @return void
   */
  protected function afterSave(): void
  {
    $this->form->model($this->getRecord())->saveRelationships();
  }

  protected function getHeaderActions(): array
  {
    return [
      InvitationAdminActions::scheduleSendForEvent(fn () => $this->getRecord()),
      DeleteAction::make(),
    ];
  }
}
