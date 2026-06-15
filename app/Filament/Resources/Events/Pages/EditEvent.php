<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Filament\Support\InvitationAdminActions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
  protected static string $resource = EventResource::class;

  protected function getHeaderActions(): array
  {
    return [
      InvitationAdminActions::scheduleSendForEvent(fn () => $this->getRecord()),
      DeleteAction::make(),
    ];
  }
}
