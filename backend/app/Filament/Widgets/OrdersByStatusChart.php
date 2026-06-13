<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\ChartWidget;

/**
 * Widget graphique — répartition des commandes par statut.
 */
class OrdersByStatusChart extends ChartWidget
{
  protected static ?int $sort = 2;

  protected int | string | array $columnSpan = 'full';

  protected ?string $heading = 'Commandes par statut';

  protected ?string $description = 'Vue d\'ensemble du pipeline commandes.';

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
    $labels = [];
    $data = [];

    foreach (OrderStatus::cases() as $status) {
      $count = Order::query()->where('status', $status)->count();
      if ($count > 0) {
        $labels[] = $status->label();
        $data[] = $count;
      }
    }

    return [
      'datasets' => [
        [
          'label' => 'Commandes',
          'data' => $data,
          'backgroundColor' => [
            '#f59e0b', '#22c55e', '#3b82f6', '#8b5cf6',
            '#06b6d4', '#10b981', '#ef4444', '#6b7280', '#9ca3af',
          ],
        ],
      ],
      'labels' => $labels,
    ];
  }
}
