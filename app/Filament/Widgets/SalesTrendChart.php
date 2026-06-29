<?php

namespace App\Filament\Widgets;

use App\Models\ShopSetting;
use App\Services\Dashboard\DashboardAnalyticsService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * Widget graphique — évolution du chiffre d'affaires.
 */
class SalesTrendChart extends ChartWidget
{
  use InteractsWithPageFilters;

  protected static ?int $sort = 5;

  protected int|string|array $columnSpan = [
    'default' => 'full',
    'md' => 2,
  ];

  protected ?string $heading = 'Évolution des ventes';

  protected ?string $description = 'Chiffre d\'affaires journalier sur la période sélectionnée.';

  /**
   * Type de graphique Chart.js.
   *
   * @return string Type de chart
   */
  protected function getType(): string
  {
    return 'line';
  }

  /**
   * Données du graphique des ventes.
   *
   * @return array<string, mixed> Dataset Chart.js
   */
  protected function getData(): array
  {
    $analytics = app(DashboardAnalyticsService::class);
    $period = $analytics->resolvePeriod($this->pageFilters);
    $series = $analytics->salesTrend($period['start'], $period['end']);
    $currency = ShopSetting::currencyCode();

    return [
      'datasets' => [
        [
          'label' => 'Chiffre d\'affaires ('.$currency.')',
          'data' => $series['values'],
          'borderColor' => '#2563eb',
          'backgroundColor' => 'rgba(37, 99, 235, 0.15)',
          'fill' => true,
          'tension' => 0.3,
        ],
      ],
      'labels' => $series['labels'],
    ];
  }
}
