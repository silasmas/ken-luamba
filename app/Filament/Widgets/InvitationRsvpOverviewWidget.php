<?php

namespace App\Filament\Widgets;

use App\Services\Invitations\InvitationAnalyticsService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Cartes statistiques des invitations et réponses RSVP.
 */
class InvitationRsvpOverviewWidget extends StatsOverviewWidget
{
  use InteractsWithPageFilters;

  protected static bool $isDiscovered = false;

  protected int|string|array $columnSpan = 'full';

  /**
   * Retourne les indicateurs d'invitations pour l'événement filtré.
   *
   * @return array<int, Stat> Cartes statistiques
   */
  protected function getStats(): array
  {
    $analytics = app(InvitationAnalyticsService::class);
    $eventId = $analytics->resolveEventId($this->pageFilters);
    $stats = $analytics->overviewStats($eventId);
    $eventLabel = $analytics->eventLabel($eventId);
    $scope = $eventLabel ?? 'Tous les événements';

    return [
      Stat::make('Invitations', (string) $stats['total'])
        ->description($scope)
        ->descriptionIcon('heroicon-m-users')
        ->color('primary'),
      Stat::make('Invitations envoyées', (string) $stats['invitationsSent'])
        ->description('Au moins un canal (email, SMS ou WhatsApp)')
        ->descriptionIcon('heroicon-m-paper-airplane')
        ->color('info'),
      Stat::make('Présents', (string) $stats['attending'])
        ->description('Réponses positives')
        ->descriptionIcon('heroicon-m-check-circle')
        ->color('success'),
      Stat::make('Absents', (string) $stats['notAttending'])
        ->description('Réponses négatives')
        ->descriptionIcon('heroicon-m-x-circle')
        ->color('danger'),
      Stat::make('En attente', (string) $stats['pending'])
        ->description('Sans réponse RSVP')
        ->descriptionIcon('heroicon-m-clock')
        ->color('warning'),
      Stat::make('Taux de réponse', $stats['responseRate'].' %')
        ->description($stats['responded'].' réponse(s) sur '.$stats['total'])
        ->descriptionIcon('heroicon-m-chart-pie')
        ->color('gray'),
    ];
  }
}
