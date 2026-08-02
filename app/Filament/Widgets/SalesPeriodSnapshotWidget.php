<?php

namespace App\Filament\Widgets;

use App\Services\Dashboard\DashboardAnalyticsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Widget ventes jour / semaine / mois — boutique vs vente directe + période explicite.
 */
class SalesPeriodSnapshotWidget extends StatsOverviewWidget
{
  protected static ?int $sort = 1;

  protected int|string|array $columnSpan = 'full';

  protected ?string $heading = 'Encaissements boutique & vente directe';

  protected ?string $description = 'Totaux selon la date de paiement — aujourd\'hui, cette semaine, ce mois';

  /**
   * Cartes CA séparées par canal et par période.
   *
   * @return array<int, Stat>
   */
  protected function getStats(): array
  {
    $analytics = app(DashboardAnalyticsService::class);
    $snapshot = $analytics->salesActivitySnapshot();
    $today = $snapshot['today'];
    $week = $snapshot['week'];
    $month = $snapshot['month'];

    return [
      Stat::make('Total aujourd\'hui', $analytics->formatMoney($today['revenue']))
        ->description($this->channelPeriodDescription($analytics, $today))
        ->descriptionIcon('heroicon-m-calendar-days')
        ->color('success'),
      Stat::make('Boutique aujourd\'hui', $analytics->formatMoney($today['shop_revenue']))
        ->description(sprintf('%d cmd payée(s) site · %s', $today['shop'], $today['period_label']))
        ->descriptionIcon('heroicon-m-globe-alt')
        ->color('info'),
      Stat::make('Direct aujourd\'hui', $analytics->formatMoney($today['direct_revenue']))
        ->description(sprintf('%d cmd payée(s) direct · %s', $today['direct'], $today['period_label']))
        ->descriptionIcon('heroicon-m-qr-code')
        ->color('warning'),
      Stat::make('Total cette semaine', $analytics->formatMoney($week['revenue']))
        ->description($this->channelPeriodDescription($analytics, $week))
        ->descriptionIcon('heroicon-m-chart-bar')
        ->color('primary'),
      Stat::make('Boutique cette semaine', $analytics->formatMoney($week['shop_revenue']))
        ->description(sprintf('%d cmd payée(s) site · %s', $week['shop'], $week['period_label']))
        ->descriptionIcon('heroicon-m-globe-alt')
        ->color('info'),
      Stat::make('Direct cette semaine', $analytics->formatMoney($week['direct_revenue']))
        ->description(sprintf('%d cmd payée(s) direct · %s', $week['direct'], $week['period_label']))
        ->descriptionIcon('heroicon-m-qr-code')
        ->color('warning'),
      Stat::make('Total ce mois', $analytics->formatMoney($month['revenue']))
        ->description($this->channelPeriodDescription($analytics, $month))
        ->descriptionIcon('heroicon-m-banknotes')
        ->color('success'),
      Stat::make('Boutique ce mois', $analytics->formatMoney($month['shop_revenue']))
        ->description(sprintf('%d cmd payée(s) site · %s', $month['shop'], $month['period_label']))
        ->descriptionIcon('heroicon-m-globe-alt')
        ->color('info'),
      Stat::make('Direct ce mois', $analytics->formatMoney($month['direct_revenue']))
        ->description(sprintf('%d cmd payée(s) direct · %s', $month['direct'], $month['period_label']))
        ->descriptionIcon('heroicon-m-qr-code')
        ->color('warning'),
      Stat::make('En attente paiement', (string) $today['pending'])
        ->description(sprintf(
          'Créées aujourd\'hui %d · semaine %d · mois %d',
          $today['pending'],
          $week['pending'],
          $month['pending'],
        ))
        ->descriptionIcon('heroicon-m-clock')
        ->color('warning'),
      Stat::make('Nouveaux clients (mois)', (string) $month['clients'])
        ->description(sprintf(
          'Semaine %d · Aujourd\'hui %d',
          $week['clients'],
          $today['clients'],
        ))
        ->descriptionIcon('heroicon-m-user-plus')
        ->color('success'),
    ];
  }

  /**
   * Description boutique + direct + libellé de période.
   *
   * @param DashboardAnalyticsService $analytics Service analytics
   * @param array<string, mixed> $data Snapshot de période
   * @return string Texte descriptif
   */
  private function channelPeriodDescription(DashboardAnalyticsService $analytics, array $data): string
  {
    return sprintf(
      'Boutique %s (%d) · Direct %s (%d) · %s',
      $analytics->formatMoney((float) $data['shop_revenue']),
      (int) $data['shop'],
      $analytics->formatMoney((float) $data['direct_revenue']),
      (int) $data['direct'],
      (string) $data['period_label'],
    );
  }
}
