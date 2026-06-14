<?php

namespace App\Filament\Resources\InvitationDispatchLogs\Pages;

use App\Filament\Resources\InvitationDispatchLogs\InvitationDispatchLogResource;
use App\Filament\Widgets\InvitationMessagingStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListInvitationDispatchLogs extends ListRecords
{
  protected static string $resource = InvitationDispatchLogResource::class;

  /**
   * Widgets affichés en haut de la page historique.
   *
   * @return array<int, class-string> Classes de widgets
   */
  protected function getHeaderWidgets(): array
  {
    return [
      InvitationMessagingStatsWidget::class,
    ];
  }
}
