<?php

namespace App\Filament\Widgets;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Orders\OrderListStatsService;
use App\Support\OrderBooksReceivedQuery;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Reactive;

/**
 * Stats dynamiques selon l'onglet actif : boutique / direct + encaissements par période.
 */
class OrderListStatsWidget extends StatsOverviewWidget
{
  protected static bool $isDiscovered = false;

  protected static ?int $sort = 1;

  protected int|string|array $columnSpan = 'full';

  protected ?string $heading = 'Stats de la vue actuelle';

  protected ?string $description = 'Boutique vs vente directe · encaissements selon date de paiement';

  /**
   * Onglet Filament courant (réactif depuis ListOrders).
   */
  #[Reactive]
  public ?string $activeTab = null;

  /**
   * Cartes statistiques pour l'onglet actif.
   *
   * @return array<int, Stat>
   */
  protected function getStats(): array
  {
    $service = app(OrderListStatsService::class);
    $stats = $service->summarize($this->queryForActiveTab());
    $currency = $stats['currency'];
    $tabLabel = $this->activeTabLabel();
    $today = $stats['periods']['today'];
    $week = $stats['periods']['week'];
    $month = $stats['periods']['month'];

    return [
      Stat::make('Commandes (vue)', (string) $stats['total'])
        ->description($tabLabel.' · Payées '.$stats['paid'].' · Non payées '.$stats['unpaid'])
        ->descriptionIcon('heroicon-m-shopping-bag')
        ->color('primary'),
      Stat::make('Encaissé boutique (vue)', $service->formatMoney($stats['shop_revenue'], $currency))
        ->description(sprintf(
          '%d cmd site · %d payée(s) · panier moyen vue %s',
          $stats['shop'],
          $stats['shop_paid'],
          $service->formatMoney($stats['average_paid'], $currency),
        ))
        ->descriptionIcon('heroicon-m-globe-alt')
        ->color('info'),
      Stat::make('Encaissé vente directe (vue)', $service->formatMoney($stats['direct_revenue'], $currency))
        ->description(sprintf(
          '%d cmd direct · %d payée(s)',
          $stats['direct'],
          $stats['direct_paid'],
        ))
        ->descriptionIcon('heroicon-m-qr-code')
        ->color('warning'),
      Stat::make('Encaissé aujourd\'hui', $service->formatMoney($today['revenue'], $currency))
        ->description(sprintf(
          'Boutique %s (%d) · Direct %s (%d) · %s',
          $service->formatMoney($today['shop_revenue'], $currency),
          $today['shop_paid'],
          $service->formatMoney($today['direct_revenue'], $currency),
          $today['direct_paid'],
          $today['label'],
        ))
        ->descriptionIcon('heroicon-m-calendar-days')
        ->color('success'),
      Stat::make('Encaissé cette semaine', $service->formatMoney($week['revenue'], $currency))
        ->description(sprintf(
          'Boutique %s (%d) · Direct %s (%d) · %s',
          $service->formatMoney($week['shop_revenue'], $currency),
          $week['shop_paid'],
          $service->formatMoney($week['direct_revenue'], $currency),
          $week['direct_paid'],
          $week['label'],
        ))
        ->descriptionIcon('heroicon-m-chart-bar')
        ->color('primary'),
      Stat::make('Encaissé ce mois', $service->formatMoney($month['revenue'], $currency))
        ->description(sprintf(
          'Boutique %s (%d) · Direct %s (%d) · %s',
          $service->formatMoney($month['shop_revenue'], $currency),
          $month['shop_paid'],
          $service->formatMoney($month['direct_revenue'], $currency),
          $month['direct_paid'],
          $month['label'],
        ))
        ->descriptionIcon('heroicon-m-banknotes')
        ->color('success'),
      Stat::make('Récupérés / à remettre', $stats['recovered'].' / '.$stats['to_collect'])
        ->description('À remettre (parcours) : '.$stats['awaiting_handover'].' · Terminées : '.$stats['completed'])
        ->descriptionIcon('heroicon-m-hand-raised')
        ->color($stats['to_collect'] > 0 ? 'warning' : 'success'),
      Stat::make('Paiements / mails', $stats['pending_payment'].' en attente')
        ->description(sprintf(
          'Échecs paiement %d · Mails achat manquants %d · %s',
          $stats['payment_failed'],
          $stats['mail_missing'],
          $service->statusBreakdownLabel($stats['by_status']),
        ))
        ->descriptionIcon('heroicon-m-credit-card')
        ->color($stats['payment_failed'] > 0 ? 'danger' : 'gray'),
    ];
  }

  /**
   * Construit la requête Eloquent correspondant à l'onglet actif.
   *
   * @return Builder Requête commandes
   */
  private function queryForActiveTab(): Builder
  {
    $query = Order::query();

    return match ($this->activeTab) {
      'shop' => $query->where('source', OrderSource::Shop->value),
      'direct_payment' => $query->where('source', OrderSource::DirectPayment->value),
      'direct_to_collect' => OrderBooksReceivedQuery::notReceived(
        $query
          ->where('source', OrderSource::DirectPayment->value)
          ->whereNotNull('paid_at'),
      ),
      'direct_collected' => OrderBooksReceivedQuery::received(
        $query
          ->where('source', OrderSource::DirectPayment->value)
          ->whereNotNull('paid_at'),
      ),
      'pending_payment' => $query->where('status', OrderStatus::PendingPayment->value),
      'paid' => $query->whereNotNull('paid_at'),
      'awaiting_handover' => $query
        ->whereNotNull('paid_at')
        ->whereIn('status', [
          OrderStatus::Paid->value,
          OrderStatus::Processing->value,
          OrderStatus::OutForDelivery->value,
          OrderStatus::DeliveredByCourier->value,
        ]),
      default => $query,
    };
  }

  /**
   * Libellé lisible de l'onglet actif.
   *
   * @return string Nom d'onglet
   */
  private function activeTabLabel(): string
  {
    return match ($this->activeTab) {
      'shop' => 'Onglet boutique',
      'direct_payment' => 'Onglet vente directe',
      'direct_to_collect' => 'Onglet direct à récupérer',
      'direct_collected' => 'Onglet direct récupéré',
      'pending_payment' => 'Onglet en attente paiement',
      'paid' => 'Onglet payées',
      'awaiting_handover' => 'Onglet à remettre',
      default => 'Toutes les commandes',
    };
  }
}
