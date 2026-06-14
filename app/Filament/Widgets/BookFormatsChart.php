<?php

namespace App\Filament\Widgets;

use App\Services\Dashboard\DashboardAnalyticsService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * Widget graphique — répartition des formats de livre par type.
 */
class BookFormatsChart extends ChartWidget
{
  use InteractsWithPageFilters;

  protected static ?int $sort = 4;

  protected int|string|array $columnSpan = [
    'default' => 'full',
    'md' => 1,
  ];

  protected ?string $heading = 'Formats par livre';

  protected ?string $description = 'Répartition des formats actifs dans le catalogue.';

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
   * Données du graphique par type de format.
   *
   * @return array<string, mixed> Dataset Chart.js
   */
  protected function getData(): array
  {
    $counts = app(DashboardAnalyticsService::class)->bookFormatsByType();

    return [
      'datasets' => [
        [
          'label' => 'Formats',
          'data' => array_values($counts),
          'backgroundColor' => ['#2563eb', '#22c55e', '#f59e0b', '#8b5cf6'],
        ],
      ],
      'labels' => array_keys($counts),
    ];
  }
}
