<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderItem;

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
    $order->loadMissing('items');

    if ($order->items->isEmpty()) {
      return '—';
    }

    return $order->items
      ->map(fn (OrderItem $item): string => sprintf(
        '%s (%s) × %d',
        $item->book_title,
        $item->format_type->label(),
        $item->quantity,
      ))
      ->implode(' · ');
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
}
