<?php

namespace App\Filament\Resources\Events\Pages;

use App\Enums\InvitationRsvpStatus;
use App\Filament\Pages\InvitationStats;
use App\Filament\Resources\Events\EventResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListEvents extends ListRecords
{
  protected static string $resource = EventResource::class;

  /**
   * Actions d'en-tête de la liste des événements.
   *
   * @return array<int, Action|CreateAction> Actions disponibles
   */
  protected function getHeaderActions(): array
  {
    return [
      Action::make('invitationStats')
        ->label('Statistiques invitations')
        ->icon(Heroicon::OutlinedChartBarSquare)
        ->url(InvitationStats::getUrl()),
      CreateAction::make(),
    ];
  }

  /**
   * Précharge les compteurs RSVP pour chaque événement listé.
   *
   * @return Builder Modificateur de requête
   */
  protected function getTableQuery(): Builder
  {
    return parent::getTableQuery()
      ->withCount([
        'invitations',
        'invitations as attending_count' => fn (Builder $query): Builder => $query
          ->where('rsvp_status', InvitationRsvpStatus::Attending),
        'invitations as not_attending_count' => fn (Builder $query): Builder => $query
          ->where('rsvp_status', InvitationRsvpStatus::NotAttending),
        'invitations as pending_count' => fn (Builder $query): Builder => $query
          ->where('rsvp_status', InvitationRsvpStatus::Pending),
      ]);
  }
}
