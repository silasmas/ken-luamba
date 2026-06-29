<?php

namespace App\Support;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
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

    return $lines === [] ? '—' : implode(' · ', $lines);
  }

  /**
   * Retourne une ligne par article commandé.
   *
   * @param Order $order Commande source
   * @return list<string> Libellés article × quantité
   */
  public static function itemsSummaryLines(Order $order): array
  {
    $order->loadMissing('items');

    if ($order->items->isEmpty()) {
      return [];
    }

    return $order->items
      ->map(fn (OrderItem $item): string => sprintf(
        '%s (%s) × %d',
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
    $lines = self::itemsSummaryLines($order);

    if ($lines === []) {
      return new HtmlString('<span class="text-gray-400">—</span>');
    }

    $markup = collect($lines)
      ->map(function (string $line): string {
        if (! preg_match('/^(.+?) \((.+?)\) × (\d+)$/', $line, $matches)) {
          return '<span class="block py-0.5 leading-snug">'.e($line).'</span>';
        }

        return sprintf(
          '<span class="block py-0.5 leading-snug"><span class="font-medium text-gray-950 dark:text-white">%s</span> <span class="text-gray-500 dark:text-gray-400">(%s)</span> <span class="font-semibold">× %s</span></span>',
          e($matches[1]),
          e($matches[2]),
          e($matches[3]),
        );
      })
      ->implode('');

    return new HtmlString($markup);
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
   * Indique si les livres physiques de la commande sont considérés comme reçus.
   *
   * @param Order $order Commande source
   * @return bool True si réception confirmée
   */
  public static function isBooksReceived(Order $order): bool
  {
    if ($order->isDigitalOnly()) {
      return false;
    }

    $order->loadMissing('delivery');

    if ($order->status === OrderStatus::Completed) {
      return true;
    }

    return in_array(
      $order->delivery?->status,
      [DeliveryStatus::Delivered, DeliveryStatus::PickedUp],
      true,
    );
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

    return self::isBooksReceived($order) ? 'Reçu' : 'Non reçu';
  }
}
