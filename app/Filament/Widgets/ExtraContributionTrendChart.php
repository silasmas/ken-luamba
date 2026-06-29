<?php

namespace App\Filament\Widgets;

use App\Models\ShopSetting;
use App\Services\Dashboard\DashboardAnalyticsService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * Widget graphique — évolution des soutiens volontaires collectés.
 */
class ExtraContributionTrendChart extends ChartWidget
{
  use InteractsWithPageFilters;

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
   * Données du graphique des soutiens volontaires.
   *
   * @return array<string, mixed> Dataset Chart.js
   */
  protected function getData(): array
  {
    $analytics = app(DashboardAnalyticsService::class);
    $period = $analytics->resolvePeriod($this->pageFilters);
    $series = $analytics->extraContributionTrend($period['start'], $period['end']);
    $currency = ShopSetting::currencyCode();

    return [
      'datasets' => [
        [
          'label' => 'Soutiens volontaires ('.$currency.')',
          'data' => $series['values'],
          'borderColor' => '#16a34a',
          'backgroundColor' => 'rgba(22, 163, 74, 0.35)',
        ],
      ],
      'labels' => $series['labels'],
    ];
  }
}
