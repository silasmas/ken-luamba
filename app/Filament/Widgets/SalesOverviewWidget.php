<?php

namespace App\Filament\Widgets;

use App\Services\Dashboard\DashboardAnalyticsService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Widget tableau de bord — indicateurs ventes filtrés par période.
 */
class SalesOverviewWidget extends StatsOverviewWidget
{
  use InteractsWithPageFilters;

  protected static ?int $sort = 2;

  protected int|string|array $columnSpan = 'full';

  protected ?string $heading = 'Période sélectionnée';

  /**
   * Retourne les statistiques affichées sur le dashboard.
   *
   * @return array<int, Stat> Cartes statistiques
   */
  protected function getStats(): array
  {
    $analytics = app(DashboardAnalyticsService::class);
    $period = $analytics->resolvePeriod($this->pageFilters);
    $activity = $analytics->activityForBounds(
      $period['start'],
      $period['end'],
      'Période sélectionnée',
    );
    $purchases = $analytics->purchasesInPeriod($period['start'], $period['end']);

    $completedPayments = \App\Models\Payment::query()
      ->where('status', \App\Enums\PaymentStatus::Completed)
      ->whereNotNull('paid_at')
      ->whereBetween('paid_at', [$period['start'], $period['end']])
      ->count();

    return [
      Stat::make('Encaissé total', $analytics->formatMoney($activity['revenue']))
        ->description($activity['period_label'])
        ->descriptionIcon('heroicon-m-banknotes')
        ->color('success'),
      Stat::make('Encaissé boutique', $analytics->formatMoney($activity['shop_revenue']))
        ->description(sprintf(
          '%d cmd site payée(s) · %s',
          $activity['shop'],
          $activity['period_label'],
        ))
        ->descriptionIcon('heroicon-m-globe-alt')
        ->color('info'),
      Stat::make('Encaissé vente directe', $analytics->formatMoney($activity['direct_revenue']))
        ->description(sprintf(
          '%d cmd direct payée(s) · %s',
          $activity['direct'],
          $activity['period_label'],
        ))
        ->descriptionIcon('heroicon-m-qr-code')
        ->color('warning'),
      Stat::make('Commandes créées', (string) $activity['orders'])
        ->description($activity['paidOrders'].' payées · '.$purchases.' articles · En attente '.$activity['pending'])
        ->descriptionIcon('heroicon-m-shopping-bag')
        ->color('primary'),
      Stat::make('Paiements confirmés', (string) $completedPayments)
        ->description(sprintf(
          'Boutique %d · Direct %d · Nouveaux clients +%d',
          $activity['shop'],
          $activity['direct'],
          $activity['clients'],
        ))
        ->descriptionIcon('heroicon-m-credit-card')
        ->color('info'),
    ];
  }
}
