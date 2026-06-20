<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\Concerns\SyncsEventAssociatedBooks;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Support\InvitationAdminActions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
  use SyncsEventAssociatedBooks;

  protected static string $resource = EventResource::class;

  /**
   * Synchronise les livres associés après la mise à jour de l'événement.
   *
   * @return void
   */
  protected function afterSave(): void
  {
    $this->syncAssociatedBooks();
  }

  protected function getHeaderActions(): array
  {
    return [
      InvitationAdminActions::scheduleSendForEvent(fn () => $this->getRecord()),
      DeleteAction::make(),
    ];
  }
}
