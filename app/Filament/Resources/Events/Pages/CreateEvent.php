<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\Concerns\SyncsEventAssociatedBooks;
use App\Filament\Resources\Events\EventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEvent extends CreateRecord
{
  use SyncsEventAssociatedBooks;

  protected static string $resource = EventResource::class;

  /**
   * Synchronise les livres associés après la création de l'événement.
   *
   * @return void
   */
  protected function afterCreate(): void
  {
    $this->syncAssociatedBooks();
  }
}
