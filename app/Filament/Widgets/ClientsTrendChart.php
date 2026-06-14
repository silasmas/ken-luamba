<?php

namespace App\Filament\Widgets;

use App\Services\Dashboard\DashboardAnalyticsService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * Widget graphique — évolution des inscriptions clients.
 */
class ClientsTrendChart extends ChartWidget
{
  use InteractsWithPageFilters;

  protected static ?int $sort = 6;

  protected int|string|array $columnSpan = [
    'default' => 'full',
    'md' => 1,
  ];

  protected ?string $heading = 'Évolution des clients';

  protected ?string $description = 'Nouveaux clients inscrits par jour.';

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
   * Données du graphique des clients.
   *
   * @return array<string, mixed> Dataset Chart.js
   */
  protected function getData(): array
  {
    $analytics = app(DashboardAnalyticsService::class);
    $period = $analytics->resolvePeriod($this->pageFilters);
    $series = $analytics->clientsTrend($period['start'], $period['end']);

    return [
      'datasets' => [
        [
          'label' => 'Nouveaux clients',
          'data' => $series['values'],
          'backgroundColor' => '#22c55e',
        ],
      ],
      'labels' => $series['labels'],
    ];
  }
}
