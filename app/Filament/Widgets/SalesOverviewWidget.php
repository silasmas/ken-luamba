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
    $activity = $analytics->activityForBounds($period['start'], $period['end']);
    $purchases = $analytics->purchasesInPeriod($period['start'], $period['end']);

    $completedPayments = \App\Models\Payment::query()
      ->where('status', \App\Enums\PaymentStatus::Completed)
      ->whereBetween('created_at', [$period['start'], $period['end']])
      ->count();

    return [
      Stat::make('Chiffre d\'affaires', $analytics->formatMoney($activity['revenue']))
        ->description('Sur la période sélectionnée ('.$analytics->shopCurrencyCode().')')
        ->descriptionIcon('heroicon-m-banknotes')
        ->color('success'),
      Stat::make('Commandes', (string) $activity['orders'])
        ->description($activity['paidOrders'].' payées · '.$purchases.' articles')
        ->descriptionIcon('heroicon-m-shopping-bag')
        ->color('primary'),
      Stat::make('En attente de paiement', (string) $activity['pending'])
        ->description('Checkout non finalisé')
        ->descriptionIcon('heroicon-m-clock')
        ->color('warning'),
      Stat::make('Paiements confirmés', (string) $completedPayments)
        ->description(sprintf(
          'Direct %d · Boutique %d · Clients +%d',
          $activity['direct'],
          $activity['shop'],
          $activity['clients'],
        ))
        ->descriptionIcon('heroicon-m-credit-card')
        ->color('info'),
    ];
  }
}
