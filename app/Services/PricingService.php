<?php

namespace App\Services;

use App\Models\BookFormat;
use App\Models\PricingPeriod;

/**
 * Service de calcul des prix actifs par format de livre.
 */
class PricingService
{
  /**
   * Retourne la période tarifaire active pour un format.
   *
   * @param BookFormat $format Format de livre cible
   * @return PricingPeriod|null Période active ou null
   */
  public function getCurrentPeriod(BookFormat $format): ?PricingPeriod
  {
    return $format->pricingPeriods()
      ->where('is_active', true)
      ->where('start_at', '<=', now())
      ->where('end_at', '>=', now())
      ->orderBy('start_at')
      ->first();
  }

  /**
   * Retourne le prix actuel formaté pour l'API.
   *
   * @param BookFormat $format Format de livre cible
   * @return array<string, mixed>|null Données de prix ou null
   */
  public function getCurrentPrice(BookFormat $format): ?array
  {
    $period = $this->getCurrentPeriod($format);

    if ($period === null) {
      return null;
    }

    return [
      'periodId' => $period->id,
      'label' => $period->label,
      'type' => $period->type->value,
      'typeLabel' => $period->type->label(),
      'price' => $period->price,
      'currency' => $period->currency,
      'startAt' => $period->start_at?->toIso8601String(),
      'endAt' => $period->end_at?->toIso8601String(),
    ];
  }
}
