<?php

namespace App\Filament\Widgets;

use App\Services\Dashboard\DashboardAnalyticsService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Widget tableau de bord — indicateurs ventes et commandes.
 */
class SalesOverviewWidget extends StatsOverviewWidget
{
  use InteractsWithPageFilters;

  protected static ?int $sort = 2;

  protected int|string|array $columnSpan = 'full';

  /**
   * Retourne les statistiques affichées sur le dashboard.
   *
   * @return array<int, Stat> Cartes statistiques
   */
  protected function getStats(): array
  {
    $analytics = app(DashboardAnalyticsService::class);
    $period = $analytics->resolvePeriod($this->pageFilters);

    $revenue = $analytics->revenueInPeriod($period['start'], $period['end']);
    $ordersCount = $analytics->ordersInPeriod($period['start'], $period['end']);
    $purchases = $analytics->purchasesInPeriod($period['start'], $period['end']);

    $pendingPayment = \App\Models\Order::query()
      ->where('status', \App\Enums\OrderStatus::PendingPayment)
      ->whereBetween('created_at', [$period['start'], $period['end']])
      ->count();

    $completedPayments = \App\Models\Payment::query()
      ->where('status', \App\Enums\PaymentStatus::Completed)
      ->whereBetween('created_at', [$period['start'], $period['end']])
      ->count();

    return [
      Stat::make('Chiffre d\'affaires', $analytics->formatMoney($revenue))
        ->description('Sur la période sélectionnée')
        ->descriptionIcon('heroicon-m-banknotes')
        ->color('success'),
      Stat::make('Commandes', (string) $ordersCount)
        ->description($purchases.' articles achetés')
        ->descriptionIcon('heroicon-m-shopping-bag')
        ->color('primary'),
      Stat::make('En attente de paiement', (string) $pendingPayment)
        ->description('Checkout non finalisé')
        ->descriptionIcon('heroicon-m-clock')
        ->color('warning'),
      Stat::make('Paiements confirmés', (string) $completedPayments)
        ->description('Transactions FlexPay validées')
        ->descriptionIcon('heroicon-m-credit-card')
        ->color('info'),
    ];
  }
}
