<?php

namespace App\Filament\Widgets;

use App\Services\Invitations\InvitationAnalyticsService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * Graphique en anneau — répartition des réponses RSVP.
 */
class InvitationRsvpStatusChart extends ChartWidget
{
  use InteractsWithPageFilters;

  protected static bool $isDiscovered = false;

  protected int|string|array $columnSpan = [
    'default' => 'full',
    'md' => 1,
  ];

  protected ?string $heading = 'Réponses RSVP';

  protected ?string $description = 'Présents, absents et en attente.';

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
   * Données du graphique RSVP.
   *
   * @return array<string, mixed> Dataset Chart.js
   */
  protected function getData(): array
  {
    $analytics = app(InvitationAnalyticsService::class);
    $eventId = $analytics->resolveEventId($this->pageFilters);
    $counts = $analytics->rsvpStatusCounts($eventId);

    $this->description = $analytics->eventLabel($eventId)
      ? 'Événement : '.$analytics->eventLabel($eventId)
      : 'Tous les événements confondus';

    return [
      'datasets' => [
        [
          'label' => 'Réponses',
          'data' => array_values($counts),
          'backgroundColor' => ['#22c55e', '#ef4444', '#f59e0b'],
        ],
      ],
      'labels' => array_keys($counts),
    ];
  }
}
