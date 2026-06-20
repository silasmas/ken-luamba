<?php

namespace App\Filament\Resources\Events\Concerns;

/**
 * Synchronise explicitement les livres associés à un événement via la table pivot.
 */
trait SyncsEventAssociatedBooks
{
  /**
   * Préremplit le formulaire avec les identifiants des livres déjà associés.
   *
   * @param array<string, mixed> $data Données du formulaire
   * @return array<string, mixed> Données enrichies
   */
  protected function mutateFormDataBeforeFill(array $data): array
  {
    $data['associated_book_ids'] = $this->getRecord()
      ->books()
      ->pluck('books.id')
      ->all();

    return $data;
  }

  /**
   * Enregistre uniquement les livres sélectionnés dans le champ « Livres associés ».
   *
   * @return void
   */
  protected function syncAssociatedBooks(): void
  {
    $bookIds = collect($this->form->getState()['associated_book_ids'] ?? [])
      ->filter()
      ->unique()
      ->values()
      ->all();

    $this->record->books()->sync($bookIds);
  }
}
