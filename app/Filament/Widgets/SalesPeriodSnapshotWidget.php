<?php

namespace App\Filament\Widgets;

use App\Services\Dashboard\DashboardAnalyticsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Widget ventes journalières, hebdomadaires et mensuelles + activité.
 */
class SalesPeriodSnapshotWidget extends StatsOverviewWidget
{
  protected static ?int $sort = 1;

  protected int|string|array $columnSpan = 'full';

  protected ?string $heading = 'Ventes & activité';

  protected ?string $description = 'Chiffre d\'affaires et activité — aujourd\'hui, cette semaine et ce mois';

  /**
   * Cartes CA / commandes / activité pour les 3 horizons.
   *
   * @return array<int, Stat>
   */
  protected function getStats(): array
  {
    $analytics = app(DashboardAnalyticsService::class);
    $snapshot = $analytics->salesActivitySnapshot();
    $currency = $analytics->shopCurrencyCode();

    return [
      Stat::make('CA aujourd\'hui', $analytics->formatMoney($snapshot['today']['revenue']))
        ->description($this->activityDescription($snapshot['today'], $currency))
        ->descriptionIcon('heroicon-m-calendar-days')
        ->color('success'),
      Stat::make('CA cette semaine', $analytics->formatMoney($snapshot['week']['revenue']))
        ->description($this->activityDescription($snapshot['week'], $currency))
        ->descriptionIcon('heroicon-m-chart-bar')
        ->color('primary'),
      Stat::make('CA ce mois', $analytics->formatMoney($snapshot['month']['revenue']))
        ->description($this->activityDescription($snapshot['month'], $currency))
        ->descriptionIcon('heroicon-m-banknotes')
        ->color('info'),
      Stat::make('En attente (aujourd\'hui)', (string) $snapshot['today']['pending'])
        ->description(sprintf(
          'Semaine %d · Mois %d · Nouveaux clients jour %d',
          $snapshot['week']['pending'],
          $snapshot['month']['pending'],
          $snapshot['today']['clients'],
        ))
        ->descriptionIcon('heroicon-m-clock')
        ->color('warning'),
      Stat::make('Vente directe (mois)', (string) $snapshot['month']['direct'])
        ->description(sprintf(
          'Boutique %d · Direct semaine %d · Direct jour %d',
          $snapshot['month']['shop'],
          $snapshot['week']['direct'],
          $snapshot['today']['direct'],
        ))
        ->descriptionIcon('heroicon-m-qr-code')
        ->color('warning'),
      Stat::make('Nouveaux clients (mois)', (string) $snapshot['month']['clients'])
        ->description(sprintf(
          'Semaine %d · Aujourd\'hui %d',
          $snapshot['week']['clients'],
          $snapshot['today']['clients'],
        ))
        ->descriptionIcon('heroicon-m-user-plus')
        ->color('success'),
    ];
  }

  /**
   * Construit la description d'activité sous une carte CA.
   *
   * @param array{revenue: float, orders: int, paidOrders: int, pending: int, clients: int, direct: int, shop: int} $data
   * @param string $currency Devise boutique
   * @return string Texte descriptif
   */
  private function activityDescription(array $data, string $currency): string
  {
    return sprintf(
      '%d cmd payées · %d créées · Direct %d / Boutique %d (%s)',
      $data['paidOrders'],
      $data['orders'],
      $data['direct'],
      $data['shop'],
      $currency,
    );
  }
}
