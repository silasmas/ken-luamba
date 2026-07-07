<?php

namespace App\Filament\Resources\Books\Pages;

use App\Filament\Resources\Books\BookResource;
use App\Filament\Support\BookExcerptExportAdminActions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBook extends EditRecord
{
  protected static string $resource = BookResource::class;

  /**
   * Actions d'en-tête de la fiche livre.
   *
   * @return array<int, mixed> Actions Filament
   */
  protected function getHeaderActions(): array
  {
    return [
      BookExcerptExportAdminActions::group(),
      DeleteAction::make(),
    ];
  }
}
