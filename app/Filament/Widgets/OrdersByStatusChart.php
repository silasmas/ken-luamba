<?php

namespace App\Filament\Widgets;

use App\Services\Dashboard\DashboardAnalyticsService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * Widget graphique — répartition des commandes par statut.
 */
class OrdersByStatusChart extends ChartWidget
{
  use InteractsWithPageFilters;

  protected static ?int $sort = 8;

  protected int|string|array $columnSpan = [
    'default' => 'full',
    'md' => 1,
  ];

  protected ?string $heading = 'Commandes par statut';

  protected ?string $description = 'Pipeline commandes sur la période sélectionnée.';

  /**
   * Type de graphique Chart.js.
   *
   * @return string Type de chart
   */
  protected function getType(): string
  {
    return 'doughnut';
  }

  /**
   * Données du graphique par statut de commande.
   *
   * @return array<string, mixed> Dataset Chart.js
   */
  protected function getData(): array
  {
    $analytics = app(DashboardAnalyticsService::class);
    $period = $analytics->resolvePeriod($this->pageFilters);
    $counts = $analytics->ordersByStatus($period['start'], $period['end']);

    return [
      'datasets' => [
        [
          'label' => 'Commandes',
          'data' => array_values($counts),
          'backgroundColor' => [
            '#f59e0b', '#22c55e', '#3b82f6', '#8b5cf6',
            '#06b6d4', '#10b981', '#ef4444', '#6b7280', '#9ca3af',
          ],
        ],
      ],
      'labels' => array_keys($counts),
    ];
  }
}
