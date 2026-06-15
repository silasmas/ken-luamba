<?php

namespace App\Services;

use App\Enums\PricingPeriodType;
use App\Models\Book;
use Illuminate\Support\Carbon;

/**
 * Calcule le statut commercial et les statistiques d'un livre catalogue.
 */
class BookCatalogService
{
  public function __construct(
    private readonly PricingService $pricingService,
  ) {}

  /**
   * Détermine le statut de disponibilité affiché sur la fiche livre.
   *
   * @param Book $book Livre avec formats chargés
   * @return string available|preorder|coming
   */
  public function availabilityStatus(Book $book): string
  {
    $activeFormats = $book->formats->where('is_active', true);

    if ($activeFormats->isEmpty()) {
      return 'coming';
    }

    $pricedFormats = $activeFormats->filter(
      fn ($format) => $this->pricingService->getCurrentPrice($format) !== null,
    );

    if ($pricedFormats->isEmpty()) {
      return 'coming';
    }

    $hasPreorder = $pricedFormats->contains(function ($format) {
      $period = $this->pricingService->getCurrentPeriod($format);

      return $period !== null && $period->type === PricingPeriodType::Preorder;
    });

    if ($hasPreorder) {
      return 'preorder';
    }

    return 'available';
  }

  /**
   * Libellé français du statut de disponibilité.
   *
   * @param string $status Statut technique
   * @return string Libellé affiché
   */
  public function availabilityLabel(string $status): string
  {
    return match ($status) {
      'preorder' => 'Bientôt disponible',
      'coming' => 'À paraître',
      default => 'Disponible',
    };
  }

  /**
   * Formate la durée de lecture pour l'API.
   *
   * @param int|null $minutes Durée en minutes
   * @return string|null Libellé lisible
   */
  public function formatReadingTime(?int $minutes): ?string
  {
    if ($minutes === null || $minutes <= 0) {
      return null;
    }

    $hours = intdiv($minutes, 60);
    $remaining = $minutes % 60;

    if ($hours > 0 && $remaining > 0) {
      return "{$hours} h {$remaining} de lecture";
    }

    if ($hours > 0) {
      return "{$hours} h de lecture";
    }

    return "{$remaining} min de lecture";
  }

  /**
   * Date de fin de la période de précommande active (la plus tardive entre les formats).
   *
   * @param Book $book Livre avec formats et périodes tarifaires chargés
   * @return Carbon|null Date/heure de fin ou null
   */
  public function preorderEndsAt(Book $book): ?Carbon
  {
    $endDates = $book->formats
      ->where('is_active', true)
      ->map(fn ($format) => $this->pricingService->getCurrentPeriod($format))
      ->filter(
        fn ($period) => $period !== null && $period->type === PricingPeriodType::Preorder,
      )
      ->map(fn ($period) => $period->end_at)
      ->filter();

    if ($endDates->isEmpty()) {
      return null;
    }

    return $endDates->max();
  }
}
