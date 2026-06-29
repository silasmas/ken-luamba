<?php

namespace App\Services\Dashboard;

use App\Enums\BookFormatType;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Book;
use App\Models\BookFormat;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShopSetting;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Agrège les indicateurs et séries temporelles du tableau de bord admin.
 */
class DashboardAnalyticsService
{
  /**
   * Statuts de commande considérés comme payés pour le chiffre d'affaires.
   *
   * @return list<OrderStatus> Statuts éligibles
   */
  public function paidOrderStatuses(): array
  {
    return [
      OrderStatus::Paid,
      OrderStatus::Processing,
      OrderStatus::OutForDelivery,
      OrderStatus::DeliveredByCourier,
      OrderStatus::Completed,
    ];
  }

  /**
   * Extrait la période analysée depuis les filtres du dashboard.
   *
   * @param array<string, mixed>|null $filters Filtres Filament
   * @return array{start: CarbonInterface, end: CarbonInterface} Bornes inclusives
   */
  public function resolvePeriod(?array $filters): array
  {
    $preset = (string) ($filters['period'] ?? '30d');

    if ($preset === 'custom') {
      $start = filled($filters['startDate'] ?? null)
        ? Carbon::parse($filters['startDate'])->startOfDay()
        : now()->subDays(29)->startOfDay();

      $end = filled($filters['endDate'] ?? null)
        ? Carbon::parse($filters['endDate'])->endOfDay()
        : now()->endOfDay();

      return ['start' => $start, 'end' => $end];
    }

    $end = now()->endOfDay();

    $start = match ($preset) {
      '7d' => now()->subDays(6)->startOfDay(),
      '90d' => now()->subDays(89)->startOfDay(),
      'year' => now()->startOfYear()->startOfDay(),
      'all' => Carbon::create(2020, 1, 1)->startOfDay(),
      default => now()->subDays(29)->startOfDay(),
    };

    return ['start' => $start, 'end' => $end];
  }

  /**
   * Code devise active configurée pour la boutique.
   *
   * @return string CDF ou USD
   */
  public function shopCurrencyCode(): string
  {
    return ShopSetting::currencyCode();
  }

  /**
   * Formate un montant avec la devise boutique active.
   *
   * @param float $amount Montant à afficher
   * @return string Montant formaté avec devise
   */
  public function formatMoney(float $amount): string
  {
    $currency = $this->shopCurrencyCode();
    $decimals = $currency === 'USD' ? 2 : 0;

    return number_format($amount, $decimals, ',', ' ').' '.$currency;
  }

  /**
   * Nombre total de clients inscrits.
   *
   * @return int Total clients
   */
  public function totalClients(): int
  {
    return User::query()->where('role', UserRole::Client)->count();
  }

  /**
   * Nombre de nouveaux clients sur la période.
   *
   * @param CarbonInterface $start Début de période
   * @param CarbonInterface $end Fin de période
   * @return int Nouveaux clients
   */
  public function newClientsInPeriod(CarbonInterface $start, CarbonInterface $end): int
  {
    return User::query()
      ->where('role', UserRole::Client)
      ->whereBetween('created_at', [$start, $end])
      ->count();
  }

  /**
   * Nombre total de livres au catalogue.
   *
   * @return int Total livres
   */
  public function totalBooks(): int
  {
    return Book::query()->count();
  }

  /**
   * Nombre de livres publiés.
   *
   * @return int Livres publiés
   */
  public function publishedBooks(): int
  {
    return Book::query()->where('is_published', true)->count();
  }

  /**
   * Répartition des formats de livre par type.
   *
   * @return array<string, int> Libellé => quantité
   */
  public function bookFormatsByType(): array
  {
    $counts = [];

    foreach (BookFormatType::cases() as $type) {
      $count = BookFormat::query()->where('type', $type)->count();

      if ($count > 0) {
        $counts[$type->label()] = $count;
      }
    }

    return $counts;
  }

  /**
   * Chiffre d'affaires sur la période.
   *
   * @param CarbonInterface $start Début de période
   * @param CarbonInterface $end Fin de période
   * @return float Montant total
   */
  public function revenueInPeriod(CarbonInterface $start, CarbonInterface $end): float
  {
    return (float) $this->paidOrdersInPeriodQuery($start, $end)->sum('total');
  }

  /**
   * Nombre de commandes sur la période.
   *
   * @param CarbonInterface $start Début de période
   * @param CarbonInterface $end Fin de période
   * @return int Commandes
   */
  public function ordersInPeriod(CarbonInterface $start, CarbonInterface $end): int
  {
    return Order::query()
      ->whereBetween('created_at', [$start, $end])
      ->count();
  }

  /**
   * Nombre d'achats (lignes de commande) sur la période.
   *
   * @param CarbonInterface $start Début de période
   * @param CarbonInterface $end Fin de période
   * @return int Lignes achetées
   */
  public function purchasesInPeriod(CarbonInterface $start, CarbonInterface $end): int
  {
    return OrderItem::query()
      ->whereHas('order', fn ($query) => $query->whereBetween('created_at', [$start, $end]))
      ->sum('quantity');
  }

  /**
   * Commandes payées sans soutien volontaire sur la période.
   *
   * @param CarbonInterface $start Début de période
   * @param CarbonInterface $end Fin de période
   * @return int Nombre de commandes au prix normal
   */
  public function normalPurchaseOrdersInPeriod(CarbonInterface $start, CarbonInterface $end): int
  {
    return $this->paidOrdersInPeriodQuery($start, $end)
      ->where(function ($query): void {
        $query
          ->whereNull('extra_contribution_amount')
          ->orWhere('extra_contribution_amount', '<=', 0);
      })
      ->count();
  }

  /**
   * Commandes payées avec un montant volontaire supplémentaire.
   *
   * @param CarbonInterface $start Début de période
   * @param CarbonInterface $end Fin de période
   * @return int Nombre de commandes avec soutien volontaire
   */
  public function voluntaryPurchaseOrdersInPeriod(CarbonInterface $start, CarbonInterface $end): int
  {
    return $this->paidOrdersInPeriodQuery($start, $end)
      ->where('extra_contribution_amount', '>', 0)
      ->count();
  }

  /**
   * Somme des soutiens volontaires sur les commandes payées.
   *
   * @param CarbonInterface $start Début de période
   * @param CarbonInterface $end Fin de période
   * @return float Total des montants supplémentaires
   */
  public function extraContributionTotalInPeriod(CarbonInterface $start, CarbonInterface $end): float
  {
    return (float) $this->paidOrdersInPeriodQuery($start, $end)
      ->where('extra_contribution_amount', '>', 0)
      ->sum('extra_contribution_amount');
  }

  /**
   * Série journalière des soutiens volontaires collectés.
   *
   * @param CarbonInterface $start Début de période
   * @param CarbonInterface $end Fin de période
   * @return array{labels: list<string>, values: list<float>} Labels et montants
   */
  public function extraContributionTrend(CarbonInterface $start, CarbonInterface $end): array
  {
    return $this->buildDailySeries(
      $start,
      $end,
      $this->paidOrdersInPeriodQuery($start, $end)
        ->selectRaw('DATE(created_at) as day, SUM(extra_contribution_amount) as aggregate')
        ->where('extra_contribution_amount', '>', 0)
        ->groupBy('day')
        ->orderBy('day')
        ->pluck('aggregate', 'day'),
    );
  }

  /**
   * Requête de base des commandes payées sur une période.
   *
   * @param CarbonInterface $start Début de période
   * @param CarbonInterface $end Fin de période
   * @return \Illuminate\Database\Eloquent\Builder<Order> Requête filtrée
   */
  private function paidOrdersInPeriodQuery(CarbonInterface $start, CarbonInterface $end)
  {
    return Order::query()
      ->where('currency', $this->shopCurrencyCode())
      ->whereIn('status', array_map(fn (OrderStatus $status): string => $status->value, $this->paidOrderStatuses()))
      ->whereBetween('created_at', [$start, $end]);
  }

  /**
   * Commandes groupées par statut sur la période.
   *
   * @param CarbonInterface $start Début de période
   * @param CarbonInterface $end Fin de période
   * @return array<string, int> Libellé => quantité
   */
  public function ordersByStatus(CarbonInterface $start, CarbonInterface $end): array
  {
    $counts = [];

    foreach (OrderStatus::cases() as $status) {
      $count = Order::query()
        ->where('status', $status)
        ->whereBetween('created_at', [$start, $end])
        ->count();

      if ($count > 0) {
        $counts[$status->label()] = $count;
      }
    }

    return $counts;
  }

  /**
   * Série journalière du chiffre d'affaires.
   *
   * @param CarbonInterface $start Début de période
   * @param CarbonInterface $end Fin de période
   * @return array{labels: list<string>, values: list<float>} Labels et montants
   */
  public function salesTrend(CarbonInterface $start, CarbonInterface $end): array
  {
    return $this->buildDailySeries(
      $start,
      $end,
      $this->paidOrdersInPeriodQuery($start, $end)
        ->selectRaw('DATE(created_at) as day, SUM(total) as aggregate')
        ->groupBy('day')
        ->orderBy('day')
        ->pluck('aggregate', 'day'),
    );
  }

  /**
   * Série journalière des nouveaux clients.
   *
   * @param CarbonInterface $start Début de période
   * @param CarbonInterface $end Fin de période
   * @return array{labels: list<string>, values: list<int>} Labels et totaux
   */
  public function clientsTrend(CarbonInterface $start, CarbonInterface $end): array
  {
    return $this->buildDailySeries(
      $start,
      $end,
      User::query()
        ->selectRaw('DATE(created_at) as day, COUNT(*) as aggregate')
        ->where('role', UserRole::Client)
        ->whereBetween('created_at', [$start, $end])
        ->groupBy('day')
        ->orderBy('day')
        ->pluck('aggregate', 'day'),
      false,
    );
  }

  /**
   * Série journalière des achats (quantités commandées).
   *
   * @param CarbonInterface $start Début de période
   * @param CarbonInterface $end Fin de période
   * @return array{labels: list<string>, values: list<int>} Labels et quantités
   */
  public function purchasesTrend(CarbonInterface $start, CarbonInterface $end): array
  {
    return $this->buildDailySeries(
      $start,
      $end,
      OrderItem::query()
        ->selectRaw('DATE(orders.created_at) as day, SUM(order_items.quantity) as aggregate')
        ->join('orders', 'orders.id', '=', 'order_items.order_id')
        ->whereBetween('orders.created_at', [$start, $end])
        ->groupBy('day')
        ->orderBy('day')
        ->pluck('aggregate', 'day'),
      false,
    );
  }

  /**
   * Construit une série temporelle complète avec zéros sur les jours sans donnée.
   *
   * @param CarbonInterface $start Début de période
   * @param CarbonInterface $end Fin de période
   * @param Collection<int|string, mixed> $aggregates Données groupées par jour
   * @param bool $asFloat True pour des montants décimaux
   * @return array{labels: list<string>, values: list<float|int>} Série complète
   */
  private function buildDailySeries(
    CarbonInterface $start,
    CarbonInterface $end,
    Collection $aggregates,
    bool $asFloat = true,
  ): array {
    $labels = [];
    $values = [];
    $cursor = $start->copy()->startOfDay();
    $endDay = $end->copy()->startOfDay();

    while ($cursor->lte($endDay)) {
      $key = $cursor->toDateString();
      $labels[] = $cursor->format('d/m');
      $raw = $aggregates[$key] ?? 0;
      $values[] = $asFloat ? (float) $raw : (int) $raw;
      $cursor->addDay();
    }

    return [
      'labels' => $labels,
      'values' => $values,
    ];
  }
}
