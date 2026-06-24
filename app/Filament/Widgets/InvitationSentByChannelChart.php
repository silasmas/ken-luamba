<?php

namespace App\Filament\Widgets;

use App\Services\Invitations\InvitationAnalyticsService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * Graphique en barres — invitations envoyées par canal.
 */
class InvitationSentByChannelChart extends ChartWidget
{
  use InteractsWithPageFilters;

  protected static bool $isDiscovered = false;

  protected int|string|array $columnSpan = [
    'default' => 'full',
    'md' => 1,
  ];

  protected ?string $heading = 'Invitations envoyées par canal';

  protected ?string $description = 'Nombre d\'invités contactés par email, SMS ou WhatsApp.';

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
   * Options Chart.js du graphique.
   *
   * @return array<string, mixed>|null Options
   */
  protected function getOptions(): array|null
  {
    return [
      'plugins' => [
        'legend' => [
          'display' => false,
        ],
      ],
      'scales' => [
        'y' => [
          'beginAtZero' => true,
          'ticks' => [
            'precision' => 0,
          ],
        ],
      ],
    ];
  }

  /**
   * Données d'envoi par canal.
   *
   * @return array<string, mixed> Dataset Chart.js
   */
  protected function getData(): array
  {
    $analytics = app(InvitationAnalyticsService::class);
    $eventId = $analytics->resolveEventId($this->pageFilters);
    $counts = $analytics->sentByChannel($eventId);

    return [
      'datasets' => [
        [
          'label' => 'Envois',
          'data' => array_values($counts),
          'backgroundColor' => ['#3b82f6', '#f59e0b', '#22c55e'],
        ],
      ],
      'labels' => array_keys($counts),
    ];
  }
}
