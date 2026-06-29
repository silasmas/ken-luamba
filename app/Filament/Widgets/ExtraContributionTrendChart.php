<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesDashboardCurrency;
use App\Services\Dashboard\DashboardAnalyticsService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * Widget graphique — évolution des soutiens volontaires collectés.
 */
class ExtraContributionTrendChart extends ChartWidget
{
  use InteractsWithPageFilters;
  use UsesDashboardCurrency;

  protected static ?int $sort = 6;

  protected int|string|array $columnSpan = [
    'default' => 'full',
    'md' => 2,
  ];

  protected ?string $heading = 'Soutiens volontaires';

  protected ?string $description = 'Montants supplémentaires versés au-delà du prix attendu, par jour.';

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
   * Options Chart.js avec devise boutique active.
   *
   * @return array<string, mixed>|null Options Chart.js
   */
  protected function getOptions(): array|null
  {
    return $this->moneyChartOptions();
  }

  /**
   * Données du graphique des soutiens volontaires.
   *
   * @return array<string, mixed> Dataset Chart.js
   */
  protected function getData(): array
  {
    $analytics = app(DashboardAnalyticsService::class);
    $period = $analytics->resolvePeriod($this->pageFilters);
    $series = $analytics->extraContributionTrend($period['start'], $period['end']);
    $currency = $this->dashboardCurrency();

    $this->description = 'Montants supplémentaires versés au-delà du prix attendu, par jour ('.$currency.').';

    return [
      'datasets' => [
        [
          'label' => 'Soutiens volontaires',
          'data' => $series['values'],
          'borderColor' => '#16a34a',
          'backgroundColor' => 'rgba(22, 163, 74, 0.35)',
        ],
      ],
      'labels' => $series['labels'],
    ];
  }
}
