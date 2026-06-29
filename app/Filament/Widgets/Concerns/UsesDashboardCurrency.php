<?php

namespace App\Filament\Widgets\Concerns;

use App\Services\Dashboard\DashboardAnalyticsService;
use Filament\Support\RawJs;

/**
 * Formate les montants des graphiques dashboard selon la devise boutique active.
 */
trait UsesDashboardCurrency
{
  /**
   * Retourne le code devise configuré pour la boutique.
   *
   * @return string CDF ou USD
   */
  protected function dashboardCurrency(): string
  {
    return app(DashboardAnalyticsService::class)->shopCurrencyCode();
  }

  /**
   * Nombre de décimales d'affichage pour une devise.
   *
   * @param string $currency Code devise ISO
   * @return int Nombre de décimales
   */
  protected function currencyDecimals(string $currency): int
  {
    return $currency === 'USD' ? 2 : 0;
  }

  /**
   * Options Chart.js pour un graphique monétaire.
   *
   * @param bool $integerTicks True pour forcer des ticks entiers
   * @return array<string, mixed> Options Chart.js
   */
  protected function moneyChartOptions(bool $integerTicks = false): array
  {
    $currency = $this->dashboardCurrency();
    $decimals = $this->currencyDecimals($currency);

    return [
      'plugins' => [
        'tooltip' => [
          'callbacks' => [
            'label' => RawJs::make(<<<JS
              function (context) {
                var label = context.dataset.label || '';

                if (label) {
                  label += ': ';
                }

                if (context.parsed.y !== null) {
                  label += new Intl.NumberFormat('fr-FR', {
                    minimumFractionDigits: {$decimals},
                    maximumFractionDigits: {$decimals},
                  }).format(context.parsed.y) + ' {$currency}';
                }

                return label;
              }
            JS),
          ],
        ],
      ],
      'scales' => [
        'y' => [
          'beginAtZero' => true,
          'ticks' => [
            'precision' => $integerTicks ? 0 : $decimals,
            'callback' => RawJs::make(<<<JS
              function (value) {
                return new Intl.NumberFormat('fr-FR', {
                  minimumFractionDigits: {$decimals},
                  maximumFractionDigits: {$decimals},
                }).format(value) + ' {$currency}';
              }
            JS),
          ],
        ],
      ],
    ];
  }
}
