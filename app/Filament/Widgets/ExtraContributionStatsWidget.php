<?php

namespace App\Filament\Widgets;

use App\Services\Dashboard\DashboardAnalyticsService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Widget tableau de bord — achats normaux vs soutiens volontaires.
 */
class ExtraContributionStatsWidget extends StatsOverviewWidget
{
  use InteractsWithPageFilters;

  protected static ?int $sort = 3;

  protected int|string|array $columnSpan = 'full';

  /**
   * Retourne les statistiques de soutien volontaire.
   *
   * @return array<int, Stat> Cartes statistiques
   */
  protected function getStats(): array
  {
    $analytics = app(DashboardAnalyticsService::class);
    $period = $analytics->resolvePeriod($this->pageFilters);

    $normalCount = $analytics->normalPurchaseOrdersInPeriod($period['start'], $period['end']);
    $voluntaryCount = $analytics->voluntaryPurchaseOrdersInPeriod($period['start'], $period['end']);
    $voluntaryTotal = $analytics->extraContributionTotalInPeriod($period['start'], $period['end']);
    $paidTotal = $normalCount + $voluntaryCount;
    $voluntaryRate = $paidTotal > 0
      ? round(($voluntaryCount / $paidTotal) * 100, 1)
      : 0.0;

    return [
      Stat::make('Achats au prix normal', (string) $normalCount)
        ->description('Commandes payées sans montant supplémentaire')
        ->descriptionIcon('heroicon-m-shopping-cart')
        ->color('gray'),
      Stat::make('Achats prix volontaire', (string) $voluntaryCount)
        ->description("{$voluntaryRate} % des commandes payées")
        ->descriptionIcon('heroicon-m-heart')
        ->color('success'),
      Stat::make('Total soutiens volontaires', $analytics->formatMoney($voluntaryTotal))
        ->description('Montants au-delà du total attendu ('.$analytics->shopCurrencyCode().')')
        ->descriptionIcon('heroicon-m-gift')
        ->color('primary'),
    ];
  }
}
