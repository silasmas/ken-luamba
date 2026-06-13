<?php

namespace App\Filament\Resources\BookReviews\Pages;

use App\Enums\BookReviewStatus;
use App\Filament\Resources\BookReviews\BookReviewResource;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;

class EditBookReview extends EditRecord
{
  protected static string $resource = BookReviewResource::class;

  /**
   * Configure le formulaire de modération.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public function form(Schema $schema): Schema
  {
    return $schema->components([
      TextInput::make('book.title')
        ->label('Livre')
        ->disabled(),
      TextInput::make('user.full_name')
        ->label('Lecteur')
        ->disabled(),
      TextInput::make('rating')
        ->label('Note')
        ->disabled(),
      Textarea::make('content')
        ->label('Témoignage')
        ->rows(6)
        ->disabled(),
      TextInput::make('author_role')
        ->label('Rôle / fonction')
        ->maxLength(120),
      Select::make('status')
        ->label('Statut')
        ->options(collect(BookReviewStatus::cases())->mapWithKeys(
          fn (BookReviewStatus $status) => [$status->value => $status->label()],
        )->all())
        ->required(),
    ]);
  }

  protected function getHeaderActions(): array
  {
    return [
      DeleteAction::make(),
    ];
  }

  /**
   * Enregistre la date de modération lors d'une approbation.
   *
   * @param array<string, mixed> $data Données du formulaire
   * @return array<string, mixed> Données enrichies
   */
  protected function mutateFormDataBeforeSave(array $data): array
  {
    if (($data['status'] ?? null) === BookReviewStatus::Approved->value) {
      $data['moderated_at'] = now();
      $data['moderated_by'] = auth()->id();
    }

    return $data;
  }
}
