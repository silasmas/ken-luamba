<?php

namespace App\Filament\Widgets;

use App\Services\Invitations\InvitationAnalyticsService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * Graphique linéaire — évolution des réponses RSVP sur 30 jours.
 */
class InvitationResponsesTrendChart extends ChartWidget
{
  use InteractsWithPageFilters;

  protected static bool $isDiscovered = false;

  protected int|string|array $columnSpan = 'full';

  protected ?string $heading = 'Évolution des réponses';

  protected ?string $description = 'Réponses présents et absents par jour (30 derniers jours).';

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
   * Options Chart.js du graphique.
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
   * Données de la courbe de réponses.
   *
   * @return array<string, mixed> Dataset Chart.js
   */
  protected function getData(): array
  {
    $analytics = app(InvitationAnalyticsService::class);
    $eventId = $analytics->resolveEventId($this->pageFilters);
    $series = $analytics->responsesTrend($eventId);

    $eventLabel = $analytics->eventLabel($eventId);
    $this->description = $eventLabel
      ? 'Événement : '.$eventLabel
      : 'Tous les événements — 30 derniers jours';

    return [
      'datasets' => [
        [
          'label' => 'Présents',
          'data' => $series['attending'],
          'borderColor' => '#22c55e',
          'backgroundColor' => 'rgba(34, 197, 94, 0.15)',
          'fill' => true,
          'tension' => 0.3,
        ],
        [
          'label' => 'Absents',
          'data' => $series['notAttending'],
          'borderColor' => '#ef4444',
          'backgroundColor' => 'rgba(239, 68, 68, 0.12)',
          'fill' => true,
          'tension' => 0.3,
        ],
      ],
      'labels' => $series['labels'],
    ];
  }
}
