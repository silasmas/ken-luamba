<?php

namespace App\Support;

use App\Enums\BookFormatType;
use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filtres Eloquent pour le statut « livre reçu » des commandes physiques.
 */
class OrderBooksReceivedQuery
{
  /**
   * Applique un filtre de réception sur une requête commandes.
   *
   * @param Builder $query Requête commandes
   * @param string|null $value Valeur du filtre (yes, no, na)
   * @return Builder Requête filtrée
   */
  public static function applyFilter(Builder $query, ?string $value): Builder
  {
    if ($value === null || $value === '') {
      return $query;
    }

    return match ($value) {
      'yes' => self::received($query),
      'no' => self::notReceived($query),
      'na' => self::digitalOnly($query),
      default => $query,
    };
  }

  /**
   * Limite aux commandes contenant au moins un format physique.
   *
   * @param Builder $query Requête commandes
   * @return Builder Requête filtrée
   */
  public static function withPhysical(Builder $query): Builder
  {
    return $query->whereHas('items', fn (Builder $items): Builder => $items->whereIn(
      'format_type',
      [BookFormatType::Hardcover->value, BookFormatType::Paperback->value],
    ));
  }

  /**
   * Limite aux commandes 100 % numériques.
   *
   * @param Builder $query Requête commandes
   * @return Builder Requête filtrée
   */
  public static function digitalOnly(Builder $query): Builder
  {
    return $query->whereDoesntHave('items', fn (Builder $items): Builder => $items->whereIn(
      'format_type',
      [BookFormatType::Hardcover->value, BookFormatType::Paperback->value],
    ));
  }

  /**
   * Commandes physiques dont le livre est considéré comme reçu.
   *
   * @param Builder $query Requête commandes
   * @return Builder Requête filtrée
   */
  public static function received(Builder $query): Builder
  {
    return self::withPhysical($query)->where(function (Builder $receivedQuery): void {
      $receivedQuery
        ->where('status', OrderStatus::Completed->value)
        ->orWhereHas('delivery', fn (Builder $delivery): Builder => $delivery->whereIn(
          'status',
          [DeliveryStatus::Delivered->value, DeliveryStatus::PickedUp->value],
        ));
    });
  }

  /**
   * Commandes physiques dont le livre n'est pas encore reçu.
   *
   * @param Builder $query Requête commandes
   * @return Builder Requête filtrée
   */
  public static function notReceived(Builder $query): Builder
  {
    return self::withPhysical($query)->where(function (Builder $notReceivedQuery): void {
      $notReceivedQuery
        ->where('status', '!=', OrderStatus::Completed->value)
        ->where(function (Builder $deliveryQuery): void {
          $deliveryQuery
            ->whereDoesntHave('delivery')
            ->orWhereHas('delivery', fn (Builder $delivery): Builder => $delivery->whereNotIn(
              'status',
              [DeliveryStatus::Delivered->value, DeliveryStatus::PickedUp->value],
            ));
        });
    });
  }
}
