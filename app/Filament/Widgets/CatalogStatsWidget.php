<?php

namespace App\Filament\Widgets;

use App\Services\Dashboard\DashboardAnalyticsService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Widget affichant les statistiques catalogue et clients.
 */
class CatalogStatsWidget extends StatsOverviewWidget
{
  use InteractsWithPageFilters;

  protected static ?int $sort = 1;

  protected int|string|array $columnSpan = 'full';

  /**
   * Retourne les statistiques clients et livres.
   *
   * @return array<int, Stat> Cartes statistiques
   */
  protected function getStats(): array
  {
    $analytics = app(DashboardAnalyticsService::class);
    $period = $analytics->resolvePeriod($this->pageFilters);

    return [
      Stat::make('Clients inscrits', (string) $analytics->totalClients())
        ->description($analytics->newClientsInPeriod($period['start'], $period['end']).' nouveaux sur la période')
        ->descriptionIcon('heroicon-m-users')
        ->color('primary'),
      Stat::make('Livres au catalogue', (string) $analytics->totalBooks())
        ->description($analytics->publishedBooks().' publiés')
        ->descriptionIcon('heroicon-m-book-open')
        ->color('info'),
      Stat::make('Formats actifs', (string) array_sum($analytics->bookFormatsByType()))
        ->description('Relié, broché, ebook, audio')
        ->descriptionIcon('heroicon-m-squares-2x2')
        ->color('success'),
    ];
  }
}
