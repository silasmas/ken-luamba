<?php

namespace App\Services\Orders;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\ShopSetting;
use App\Support\OrderBooksReceivedQuery;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Calcule les statistiques agrégées pour la liste admin des commandes (onglet + filtres).
 */
class OrderListStatsService
{
  /**
   * Agrège les indicateurs utiles sur une requête déjà filtrée.
   *
   * @param Builder $query Requête commandes (onglet / filtres Filament)
   * @return array{
   *   total: int,
   *   paid: int,
   *   unpaid: int,
   *   revenue: float,
   *   average_paid: float,
   *   shop: int,
   *   direct: int,
   *   shop_paid: int,
   *   direct_paid: int,
   *   shop_revenue: float,
   *   direct_revenue: float,
   *   pending_payment: int,
   *   payment_failed: int,
   *   completed: int,
   *   awaiting_handover: int,
   *   recovered: int,
   *   to_collect: int,
   *   mail_missing: int,
   *   by_status: array<string, int>,
   *   periods: array{
   *     today: array{label: string, revenue: float, shop_revenue: float, direct_revenue: float, shop_paid: int, direct_paid: int},
   *     week: array{label: string, revenue: float, shop_revenue: float, direct_revenue: float, shop_paid: int, direct_paid: int},
   *     month: array{label: string, revenue: float, shop_revenue: float, direct_revenue: float, shop_paid: int, direct_paid: int}
   *   },
   *   currency: string
   * }
   */
  public function summarize(Builder $query): array
  {
    $total = (clone $query)->count();
    $paid = (clone $query)->whereNotNull('paid_at')->count();
    $unpaid = max(0, $total - $paid);
    $revenue = (float) ((clone $query)->whereNotNull('paid_at')->sum('total') ?? 0);
    $averagePaid = $paid > 0 ? $revenue / $paid : 0.0;

    $shop = (clone $query)
      ->where('source', OrderSource::Shop->value)
      ->count();

    $direct = (clone $query)
      ->where('source', OrderSource::DirectPayment->value)
      ->count();

    $shopPaid = (clone $query)
      ->where('source', OrderSource::Shop->value)
      ->whereNotNull('paid_at')
      ->count();

    $directPaid = (clone $query)
      ->where('source', OrderSource::DirectPayment->value)
      ->whereNotNull('paid_at')
      ->count();

    $shopRevenue = (float) ((clone $query)
      ->where('source', OrderSource::Shop->value)
      ->whereNotNull('paid_at')
      ->sum('total') ?? 0);

    $directRevenue = (float) ((clone $query)
      ->where('source', OrderSource::DirectPayment->value)
      ->whereNotNull('paid_at')
      ->sum('total') ?? 0);

    $pendingPayment = (clone $query)
      ->where('status', OrderStatus::PendingPayment->value)
      ->count();

    $paymentFailed = (clone $query)
      ->whereHas('payment', fn (Builder $payment): Builder => $payment->whereIn('status', [
        PaymentStatus::Failed->value,
        PaymentStatus::Cancelled->value,
      ]))
      ->count();

    $completed = (clone $query)
      ->where('status', OrderStatus::Completed->value)
      ->count();

    $awaitingHandover = (clone $query)
      ->whereNotNull('paid_at')
      ->whereIn('status', [
        OrderStatus::Paid->value,
        OrderStatus::Processing->value,
        OrderStatus::OutForDelivery->value,
        OrderStatus::DeliveredByCourier->value,
      ])
      ->count();

    $recovered = OrderBooksReceivedQuery::received(
      (clone $query)->whereNotNull('paid_at'),
    )->count();

    $toCollect = OrderBooksReceivedQuery::notReceived(
      (clone $query)->whereNotNull('paid_at'),
    )->count();

    $mailMissing = (clone $query)
      ->whereNotNull('paid_at')
      ->whereNull('payment_success_email_sent_at')
      ->count();

    $byStatus = (clone $query)
      ->reorder()
      ->selectRaw('status, COUNT(*) as aggregate')
      ->groupBy('status')
      ->pluck('aggregate', 'status')
      ->map(fn ($count): int => (int) $count)
      ->all();

    return [
      'total' => $total,
      'paid' => $paid,
      'unpaid' => $unpaid,
      'revenue' => $revenue,
      'average_paid' => $averagePaid,
      'shop' => $shop,
      'direct' => $direct,
      'shop_paid' => $shopPaid,
      'direct_paid' => $directPaid,
      'shop_revenue' => $shopRevenue,
      'direct_revenue' => $directRevenue,
      'pending_payment' => $pendingPayment,
      'payment_failed' => $paymentFailed,
      'completed' => $completed,
      'awaiting_handover' => $awaitingHandover,
      'recovered' => $recovered,
      'to_collect' => $toCollect,
      'mail_missing' => $mailMissing,
      'by_status' => $byStatus,
      'periods' => [
        'today' => $this->encashmentForPeriod(
          $query,
          now()->startOfDay(),
          now()->endOfDay(),
          'Aujourd\'hui',
        ),
        'week' => $this->encashmentForPeriod(
          $query,
          now()->startOfWeek()->startOfDay(),
          now()->endOfDay(),
          'Cette semaine',
        ),
        'month' => $this->encashmentForPeriod(
          $query,
          now()->startOfMonth()->startOfDay(),
          now()->endOfDay(),
          'Ce mois',
        ),
      ],
      'currency' => ShopSetting::currencyCode(),
    ];
  }

  /**
   * Encaissements (selon paid_at) boutique vs direct sur une période.
   *
   * @param Builder $query Requête de base (onglet)
   * @param CarbonInterface $start Début
   * @param CarbonInterface $end Fin
   * @param string $title Titre court de période
   * @return array{label: string, revenue: float, shop_revenue: float, direct_revenue: float, shop_paid: int, direct_paid: int}
   */
  public function encashmentForPeriod(
    Builder $query,
    CarbonInterface $start,
    CarbonInterface $end,
    string $title,
  ): array {
    $paidQuery = (clone $query)
      ->whereNotNull('paid_at')
      ->whereBetween('paid_at', [$start, $end]);

    $shopPaid = (clone $paidQuery)
      ->where('source', OrderSource::Shop->value)
      ->count();

    $directPaid = (clone $paidQuery)
      ->where('source', OrderSource::DirectPayment->value)
      ->count();

    $shopRevenue = (float) ((clone $paidQuery)
      ->where('source', OrderSource::Shop->value)
      ->sum('total') ?? 0);

    $directRevenue = (float) ((clone $paidQuery)
      ->where('source', OrderSource::DirectPayment->value)
      ->sum('total') ?? 0);

    $timezone = (string) config('app.timezone', 'Africa/Kinshasa');

    return [
      'label' => sprintf(
        '%s · %s → %s (paiement, %s)',
        $title,
        $start->copy()->timezone($timezone)->format('d/m/Y H:i'),
        $end->copy()->timezone($timezone)->format('d/m/Y H:i'),
        str_replace('_', ' ', \Illuminate\Support\Str::afterLast($timezone, '/')),
      ),
      'revenue' => $shopRevenue + $directRevenue,
      'shop_revenue' => $shopRevenue,
      'direct_revenue' => $directRevenue,
      'shop_paid' => $shopPaid,
      'direct_paid' => $directPaid,
    ];
  }

  /**
   * Formate un montant avec la devise boutique.
   *
   * @param float $amount Montant
   * @param string $currency Code devise
   * @return string Montant lisible
   */
  public function formatMoney(float $amount, string $currency): string
  {
    $decimals = $currency === 'USD' ? 2 : 0;

    return number_format($amount, $decimals, ',', ' ').' '.$currency;
  }

  /**
   * Résume les volumes par statut commande pour une description UI.
   *
   * @param array<string, int> $byStatus Compteurs indexés par valeur enum
   * @return string Texte compact
   */
  public function statusBreakdownLabel(array $byStatus): string
  {
    if ($byStatus === []) {
      return 'Aucun statut';
    }

    $parts = [];

    foreach (OrderStatus::cases() as $status) {
      $count = (int) ($byStatus[$status->value] ?? 0);

      if ($count <= 0) {
        continue;
      }

      $parts[] = $status->label().' '.$count;
    }

    return $parts !== [] ? implode(' · ', $parts) : 'Aucun statut';
  }
}
