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
 * Stats dynamiques selon l'onglet actif de la liste commandes.
 *
 * N'utilise pas InteractsWithPageTable : Livewire peut envoyer null sur
 * tableColumnSearches au changement d'onglet (TypeError PHP 8.3).
 */
class OrderListStatsWidget extends StatsOverviewWidget
{
  protected static bool $isDiscovered = false;

  protected static ?int $sort = 1;

  protected int|string|array $columnSpan = 'full';

  protected ?string $heading = 'Stats de la vue actuelle';

  protected ?string $description = 'Totaux selon l\'onglet actif';

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

    return [
      Stat::make('Commandes (vue)', (string) $stats['total'])
        ->description($tabLabel.' · Boutique '.$stats['shop'].' · Direct '.$stats['direct'])
        ->descriptionIcon('heroicon-m-shopping-bag')
        ->color('primary'),
      Stat::make('Total encaissé', $service->formatMoney($stats['revenue'], $currency))
        ->description(sprintf(
          '%d payée(s) · panier moyen %s',
          $stats['paid'],
          $service->formatMoney($stats['average_paid'], $currency),
        ))
        ->descriptionIcon('heroicon-m-banknotes')
        ->color('success'),
      Stat::make('Payées / non payées', $stats['paid'].' / '.$stats['unpaid'])
        ->description('En attente paiement : '.$stats['pending_payment'].' · Échecs paiement : '.$stats['payment_failed'])
        ->descriptionIcon('heroicon-m-credit-card')
        ->color($stats['payment_failed'] > 0 ? 'danger' : 'warning'),
      Stat::make('Récupérés / à remettre', $stats['recovered'].' / '.$stats['to_collect'])
        ->description('À remettre (parcours) : '.$stats['awaiting_handover'].' · Terminées : '.$stats['completed'])
        ->descriptionIcon('heroicon-m-hand-raised')
        ->color($stats['to_collect'] > 0 ? 'warning' : 'success'),
      Stat::make('Mails achat manquants', (string) $stats['mail_missing'])
        ->description('Commandes payées sans mail d\'achat journalisé')
        ->descriptionIcon('heroicon-m-envelope')
        ->color($stats['mail_missing'] > 0 ? 'warning' : 'success'),
      Stat::make('Répartition statuts', (string) count(array_filter($stats['by_status'])))
        ->description($service->statusBreakdownLabel($stats['by_status']))
        ->descriptionIcon('heroicon-m-queue-list')
        ->color('gray'),
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
