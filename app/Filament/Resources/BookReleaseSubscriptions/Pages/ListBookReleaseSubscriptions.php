<?php

namespace App\Filament\Resources\BookReleaseSubscriptions\Pages;

use App\Filament\Resources\BookReleaseSubscriptions\BookReleaseSubscriptionResource;
use App\Filament\Support\BookReleaseAdminActions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

/**
 * Page de liste des inscriptions alerte sortie.
 */
class ListBookReleaseSubscriptions extends ListRecords
{
  protected static string $resource = BookReleaseSubscriptionResource::class;

  protected static ?string $title = 'Alertes sortie';

  /**
   * Actions d'en-tête de la liste.
   *
   * @return list<Action>
   */
  protected function getHeaderActions(): array
  {
    return [
      BookReleaseAdminActions::scheduleEmailCampaign(),
      BookReleaseAdminActions::sendEmailToAllPending(),
    ];
  }
}
