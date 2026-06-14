<?php

namespace App\Filament\Widgets;

use App\Services\Dashboard\DashboardAnalyticsService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * Widget graphique — évolution des achats (quantités commandées).
 */
class PurchasesTrendChart extends ChartWidget
{
  use InteractsWithPageFilters;

  protected static ?int $sort = 7;

  protected int|string|array $columnSpan = [
    'default' => 'full',
    'md' => 2,
  ];

  protected ?string $heading = 'Évolution des achats';

  protected ?string $description = 'Quantités achetées par jour sur la période.';

  /**
   * Type de graphique Chart.js.
   *
   * @return string Type de chart
   */
  protected function getType(): string
  {
    return 'bar';
  }

  /**
   * Données du graphique des achats.
   *
   * @return array<string, mixed> Dataset Chart.js
   */
  protected function getData(): array
  {
    $analytics = app(DashboardAnalyticsService::class);
    $period = $analytics->resolvePeriod($this->pageFilters);
    $series = $analytics->purchasesTrend($period['start'], $period['end']);

    return [
      'datasets' => [
        [
          'label' => 'Quantités achetées',
          'data' => $series['values'],
          'backgroundColor' => '#f59e0b',
        ],
      ],
      'labels' => $series['labels'],
    ];
  }
}
