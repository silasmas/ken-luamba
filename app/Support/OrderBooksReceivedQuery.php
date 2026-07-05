<?php

namespace App\Support;

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
   * @param string|null $value Valeur du filtre (yes, partial, no, na)
   * @return Builder Requête filtrée
   */
  public static function applyFilter(Builder $query, ?string $value): Builder
  {
    if ($value === null || $value === '') {
      return $query;
    }

    return match ($value) {
      'yes' => self::received($query),
      'partial' => self::partiallyReceived($query),
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
      self::physicalFormatValues(),
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
      self::physicalFormatValues(),
    ));
  }

  /**
   * Commandes physiques entièrement reçues.
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
          ['delivered', 'picked_up'],
        ))
        ->orWhereDoesntHave('items', fn (Builder $items): Builder => $items
          ->whereIn('format_type', self::physicalFormatValues())
          ->whereNull('received_at'));
    });
  }

  /**
   * Commandes physiques partiellement reçues.
   *
   * @param Builder $query Requête commandes
   * @return Builder Requête filtrée
   */
  public static function partiallyReceived(Builder $query): Builder
  {
    return self::withPhysical($query)
      ->where('status', '!=', OrderStatus::Completed->value)
      ->whereHas('items', fn (Builder $items): Builder => $items
        ->whereIn('format_type', self::physicalFormatValues())
        ->whereNotNull('received_at'))
      ->whereHas('items', fn (Builder $items): Builder => $items
        ->whereIn('format_type', self::physicalFormatValues())
        ->whereNull('received_at'));
  }

  /**
   * Commandes physiques sans aucun article reçu.
   *
   * @param Builder $query Requête commandes
   * @return Builder Requête filtrée
   */
  public static function notReceived(Builder $query): Builder
  {
    return self::withPhysical($query)
      ->where('status', '!=', OrderStatus::Completed->value)
      ->whereDoesntHave('items', fn (Builder $items): Builder => $items
        ->whereIn('format_type', self::physicalFormatValues())
        ->whereNotNull('received_at'))
      ->where(function (Builder $deliveryQuery): void {
        $deliveryQuery
          ->whereDoesntHave('delivery')
          ->orWhereHas('delivery', fn (Builder $delivery): Builder => $delivery->whereNotIn(
            'status',
            ['delivered', 'picked_up'],
          ));
      });
  }

  /**
   * Retourne les valeurs enum des formats physiques.
   *
   * @return list<string> Codes hardcover et paperback
   */
  private static function physicalFormatValues(): array
  {
    return [
      \App\Enums\BookFormatType::Hardcover->value,
      \App\Enums\BookFormatType::Paperback->value,
    ];
  }
}
