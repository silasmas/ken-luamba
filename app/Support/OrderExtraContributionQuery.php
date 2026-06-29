<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Filtres Eloquent pour distinguer achat normal et soutien volontaire.
 */
class OrderExtraContributionQuery
{
  /**
   * Applique un filtre de mode d'achat sur une requête commandes.
   *
   * @param Builder $query Requête commandes
   * @param string|null $value Valeur du filtre (normal, voluntary)
   * @return Builder Requête filtrée
   */
  public static function applyFilter(Builder $query, ?string $value): Builder
  {
    if ($value === null || $value === '') {
      return $query;
    }

    return match ($value) {
      'normal' => self::normalOnly($query),
      'voluntary' => self::voluntaryOnly($query),
      default => $query,
    };
  }

  /**
   * Limite aux commandes sans montant volontaire supplémentaire.
   *
   * @param Builder $query Requête commandes
   * @return Builder Requête filtrée
   */
  public static function normalOnly(Builder $query): Builder
  {
    return $query->where(function (Builder $normalQuery): void {
      $normalQuery
        ->whereNull('extra_contribution_amount')
        ->orWhere('extra_contribution_amount', '<=', 0);
    });
  }

  /**
   * Limite aux commandes avec un soutien volontaire au-delà du total attendu.
   *
   * @param Builder $query Requête commandes
   * @return Builder Requête filtrée
   */
  public static function voluntaryOnly(Builder $query): Builder
  {
    return $query->where('extra_contribution_amount', '>', 0);
  }
}
