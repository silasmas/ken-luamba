<?php

namespace App\Filament\Resources\BookReleaseSubscriptions\Pages;

use App\Filament\Resources\BookReleaseSubscriptions\BookReleaseSubscriptionResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Page de liste des inscriptions alerte sortie.
 */
class ListBookReleaseSubscriptions extends ListRecords
{
  protected static string $resource = BookReleaseSubscriptionResource::class;

  protected static ?string $title = 'Alertes sortie';
}
