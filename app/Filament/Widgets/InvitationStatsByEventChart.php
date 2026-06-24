<?php

namespace App\Filament\Widgets;

use App\Services\Invitations\InvitationAnalyticsService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * Graphique comparatif — statistiques RSVP par événement.
 */
class InvitationStatsByEventChart extends ChartWidget
{
  use InteractsWithPageFilters;

  protected static bool $isDiscovered = false;

  protected int|string|array $columnSpan = 'full';

  protected ?string $heading = 'Réponses par événement';

  protected ?string $description = 'Présents, absents et en attente pour chaque événement.';

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
   * Options Chart.js du graphique empilé.
   *
   * @return array<string, mixed>|null Options
   */
  protected function getOptions(): array|null
  {
    return [
      'plugins' => [
        'legend' => [
          'position' => 'bottom',
        ],
      ],
      'scales' => [
        'x' => [
          'stacked' => true,
        ],
        'y' => [
          'stacked' => true,
          'beginAtZero' => true,
          'ticks' => [
            'precision' => 0,
          ],
        ],
      ],
    ];
  }

  protected function getData(): array
  {
    $analytics = app(InvitationAnalyticsService::class);
    $eventId = $analytics->resolveEventId($this->pageFilters);
    $series = $analytics->statsByEvent($eventId);

    $this->description = $analytics->eventLabel($eventId)
      ? 'Événement : '.$analytics->eventLabel($eventId)
      : '12 derniers événements avec invitations';

    return [
      'datasets' => [
        [
          'label' => 'Présents',
          'data' => $series['attending'],
          'backgroundColor' => '#22c55e',
        ],
        [
          'label' => 'Absents',
          'data' => $series['notAttending'],
          'backgroundColor' => '#ef4444',
        ],
        [
          'label' => 'En attente',
          'data' => $series['pending'],
          'backgroundColor' => '#f59e0b',
        ],
      ],
      'labels' => $series['labels'],
    ];
  }
}
