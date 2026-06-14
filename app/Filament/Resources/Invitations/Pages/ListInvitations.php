<?php

namespace App\Filament\Resources\Invitations\Pages;

use App\Filament\Resources\Invitations\InvitationResource;
use App\Filament\Widgets\InvitationMessagingStatsWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInvitations extends ListRecords
{
  protected static string $resource = InvitationResource::class;

  /**
   * Widgets affichés en haut de la liste des invitations.
   *
   * @return array<int, class-string> Classes de widgets
   */
  protected function getHeaderWidgets(): array
  {
    return [
      InvitationMessagingStatsWidget::class,
    ];
  }

  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make(),
    ];
  }
}
