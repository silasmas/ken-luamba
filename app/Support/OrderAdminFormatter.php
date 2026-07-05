<?php

namespace App\Support;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

/**
 * Formate les informations commande pour l'admin Filament.
 */
class OrderAdminFormatter
{
  /**
   * Résume les articles d'une commande (titre, format, quantité).
   *
   * @param Order $order Commande source
   * @return string Ligne lisible pour la livraison
   */
  public static function itemsSummary(Order $order): string
  {
    $lines = self::itemsSummaryLines($order);

    return $lines === [] ? '—' : implode("\n", $lines);
  }

  /**
   * Retourne une ligne par article commandé.
   *
   * @param Order $order Commande source
   * @return list<string> Libellés article + quantité
   */
  public static function itemsSummaryLines(Order $order): array
  {
    $order->loadMissing('items');

    if ($order->items->isEmpty()) {
      return [];
    }

    return $order->items
      ->map(fn (OrderItem $item): string => sprintf(
        "%s (%s)\n× %d",
        $item->book_title,
        $item->format_type->label(),
        $item->quantity,
      ))
      ->values()
      ->all();
  }

  /**
   * Affiche les articles en HTML empilé pour le tableau Filament.
   *
   * @param Order $order Commande source
   * @return HtmlString Markup une ligne par article
   */
  public static function itemsSummaryHtml(Order $order): HtmlString
  {
    $order->loadMissing('items');

    if ($order->items->isEmpty()) {
      return new HtmlString('<span class="text-gray-400">—</span>');
    }

    $markup = $order->items
      ->map(function (OrderItem $item): string {
        return sprintf(
          '<div class="py-2 leading-snug">'
          .'<div class="font-medium text-gray-950 dark:text-white">%s</div>'
          .'<div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">%s</div>'
          .'<div class="mt-1 text-sm font-semibold text-gray-700 dark:text-gray-200">× %d</div>'
          .'</div>',
          e($item->book_title),
          e($item->format_type->label()),
          $item->quantity,
        );
      })
      ->implode('');

    return new HtmlString('<div class="min-w-[16rem] max-w-[22rem] divide-y divide-gray-100 dark:divide-white/10">'.$markup.'</div>');
  }

  /**
   * Retourne les lignes physiques d'une commande.
   *
   * @param Order $order Commande source
   * @return Collection<int, OrderItem> Articles reliés ou brochés
   */
  public static function physicalItems(Order $order): Collection
  {
    $order->loadMissing('items');

    return $order->items->filter(fn (OrderItem $item): bool => $item->isPhysical())->values();
  }

  /**
   * Compte les articles physiques reçus et le total attendu.
   *
   * @param Order $order Commande source
   * @return array{received: int, total: int} Progression de réception
   */
  public static function booksReceivedCounts(Order $order): array
  {
    $physicalItems = self::physicalItems($order);
    $total = $physicalItems->count();

    if ($total === 0) {
      return ['received' => 0, 'total' => 0];
    }

    if (self::usesLegacyFullReceipt($order)) {
      return ['received' => $total, 'total' => $total];
    }

    $received = $physicalItems->filter(fn (OrderItem $item): bool => $item->received_at !== null)->count();

    return ['received' => $received, 'total' => $total];
  }

  /**
   * Indique si la commande repose sur l'ancien marquage global « tout reçu ».
   *
   * @param Order $order Commande source
   * @return bool True si réception globale sans détail par article
   */
  public static function usesLegacyFullReceipt(Order $order): bool
  {
    if ($order->isDigitalOnly()) {
      return false;
    }

    $order->loadMissing('delivery');

    $isGloballyReceived = $order->status === OrderStatus::Completed
      || in_array(
        $order->delivery?->status,
        [DeliveryStatus::Delivered, DeliveryStatus::PickedUp],
        true,
      );

    if (! $isGloballyReceived) {
      return false;
    }

    return self::physicalItems($order)->every(fn (OrderItem $item): bool => $item->received_at === null);
  }

  /**
   * Retourne les coordonnées client utiles à la livraison.
   *
   * @param Order $order Commande source
   * @return string Email et téléphone disponibles
   */
  public static function clientContact(Order $order): string
  {
    $order->loadMissing('user');
    $parts = [];

    if (filled($order->user?->email)) {
      $parts[] = (string) $order->user->email;
    }

    $userPhone = trim((string) ($order->user?->phone ?? ''));

    if ($userPhone !== '') {
      $parts[] = $userPhone;
    }

    $shippingPhone = trim((string) ($order->shipping_address['phone'] ?? ''));

    if ($shippingPhone !== '' && $shippingPhone !== $userPhone) {
      $parts[] = 'Livraison : '.$shippingPhone;
    }

    return $parts !== [] ? implode(' · ', $parts) : '—';
  }

  /**
   * Indique si tous les livres physiques sont reçus.
   *
   * @param Order $order Commande source
   * @return bool True si réception complète
   */
  public static function isBooksReceived(Order $order): bool
  {
    if ($order->isDigitalOnly()) {
      return false;
    }

    $counts = self::booksReceivedCounts($order);

    return $counts['total'] > 0 && $counts['received'] === $counts['total'];
  }

  /**
   * Indique si au moins un article physique est reçu sans réception complète.
   *
   * @param Order $order Commande source
   * @return bool True si réception partielle
   */
  public static function isBooksPartiallyReceived(Order $order): bool
  {
    if ($order->isDigitalOnly()) {
      return false;
    }

    $counts = self::booksReceivedCounts($order);

    return $counts['received'] > 0 && $counts['received'] < $counts['total'];
  }

  /**
   * Libellé admin du statut de réception des livres.
   *
   * @param Order $order Commande source
   * @return string Libellé affiché dans Filament
   */
  public static function booksReceivedLabel(Order $order): string
  {
    if ($order->isDigitalOnly()) {
      return 'Numérique';
    }

    $counts = self::booksReceivedCounts($order);

    if ($counts['total'] === 0) {
      return '—';
    }

    if ($counts['received'] === $counts['total']) {
      return 'Reçu';
    }

    if ($counts['received'] === 0) {
      return 'Non reçu';
    }

    return sprintf('Partiel (%d/%d)', $counts['received'], $counts['total']);
  }

  /**
   * Détail des articles encore à remettre au client.
   *
   * @param Order $order Commande source
   * @return string|null Liste courte ou null si tout est reçu
   */
  public static function booksPendingSummary(Order $order): ?string
  {
    if ($order->isDigitalOnly() || self::isBooksReceived($order)) {
      return null;
    }

    $pending = self::physicalItems($order)
      ->filter(fn (OrderItem $item): bool => $item->received_at === null)
      ->map(fn (OrderItem $item): string => sprintf('%s × %d', $item->book_title, $item->quantity))
      ->values()
      ->all();

    return $pending === [] ? null : implode(' · ', $pending);
  }

  /**
   * Libellé d'une ligne physique pour la modale de réception.
   *
   * @param OrderItem $item Ligne de commande
   * @return string Libellé checkbox
   */
  public static function physicalItemReceiptLabel(OrderItem $item): string
  {
    return sprintf('%s (%s) × %d', $item->book_title, $item->format_type->label(), $item->quantity);
  }

  /**
   * Identifiants des articles physiques déjà reçus.
   *
   * @param Order $order Commande source
   * @return list<string> UUID des lignes cochées par défaut
   */
  public static function defaultReceivedItemIds(Order $order): array
  {
    if (self::usesLegacyFullReceipt($order)) {
      return self::physicalItems($order)->pluck('id')->all();
    }

    return self::physicalItems($order)
      ->filter(fn (OrderItem $item): bool => $item->received_at !== null)
      ->pluck('id')
      ->all();
  }

  /**
   * Montant de soutien volontaire ajouté par le client.
   *
   * @param Order $order Commande source
   * @return float Montant supplémentaire (0 si aucun)
   */
  public static function extraContributionAmount(Order $order): float
  {
    return max(0, (float) ($order->extra_contribution_amount ?? 0));
  }

  /**
   * Indique si le client a payé au-delà du montant attendu.
   *
   * @param Order $order Commande source
   * @return bool True si un soutien volontaire est présent
   */
  public static function hasExtraContribution(Order $order): bool
  {
    return self::extraContributionAmount($order) > 0;
  }

  /**
   * Total attendu sans le soutien volontaire.
   *
   * @param Order $order Commande source
   * @return float Montant catalogue + livraison − remise
   */
  public static function expectedTotalAmount(Order $order): float
  {
    return max(0, (float) $order->total - self::extraContributionAmount($order));
  }

  /**
   * Libellé du mode d'achat pour l'admin.
   *
   * @param Order $order Commande source
   * @return string Prix normal ou prix volontaire
   */
  public static function paymentModeLabel(Order $order): string
  {
    return self::hasExtraContribution($order) ? 'Prix volontaire' : 'Prix normal';
  }

  /**
   * Détail du montant attendu et du soutien volontaire.
   *
   * @param Order $order Commande source
   * @return string|null Description courte ou null si achat normal
   */
  public static function paymentModeDescription(Order $order): ?string
  {
    if (! self::hasExtraContribution($order)) {
      return null;
    }

    return sprintf(
      'Attendu %s %s · Soutien +%s %s',
      number_format(self::expectedTotalAmount($order), 0, ',', ' '),
      $order->currency,
      number_format(self::extraContributionAmount($order), 0, ',', ' '),
      $order->currency,
    );
  }
}
