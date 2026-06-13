<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Widget tableau de bord — indicateurs ventes et commandes.
 */
class SalesOverviewWidget extends StatsOverviewWidget
{
  protected static ?int $sort = 1;

  protected int | string | array $columnSpan = 'full';

  /**
   * Retourne les statistiques affichées sur le dashboard.
   *
   * @return array<int, Stat> Cartes statistiques
   */
  protected function getStats(): array
  {
    $paidStatuses = [
      OrderStatus::Paid,
      OrderStatus::Processing,
      OrderStatus::OutForDelivery,
      OrderStatus::DeliveredByCourier,
      OrderStatus::Completed,
    ];

    $revenue = (float) Order::query()
      ->whereIn('status', array_map(fn (OrderStatus $s) => $s->value, $paidStatuses))
      ->sum('total');

    $ordersToday = Order::query()->whereDate('created_at', today())->count();
    $pendingPayment = Order::query()->where('status', OrderStatus::PendingPayment)->count();
    $completedPayments = Payment::query()->where('status', PaymentStatus::Completed)->count();

    return [
      Stat::make('Chiffre d\'affaires', number_format($revenue, 0, ',', ' ').' CDF')
        ->description('Commandes payées et en cours')
        ->descriptionIcon('heroicon-m-banknotes')
        ->color('success'),
      Stat::make('Commandes aujourd\'hui', (string) $ordersToday)
        ->description('Nouvelles commandes du jour')
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
