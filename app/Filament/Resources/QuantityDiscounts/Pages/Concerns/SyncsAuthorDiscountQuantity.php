<?php

namespace App\Filament\Resources\QuantityDiscounts\Pages\Concerns;

use App\Enums\DiscountAppliesTo;
use App\Services\DiscountService;

/**
 * Synchronise la quantité minimale pour les remises « collection complète ».
 */
trait SyncsAuthorDiscountQuantity
{
  /**
   * Ajuste les données avant enregistrement d'une remise.
   *
   * @param array<string, mixed> $data Données du formulaire
   * @return array<string, mixed> Données normalisées
   */
  protected function mutateFormDataBeforeSave(array $data): array
  {
    if (
      ($data['applies_to'] ?? null) === DiscountAppliesTo::AuthorCompleteSet->value
      && ! empty($data['author_id'])
    ) {
      $data['min_quantity'] = app(DiscountService::class)
        ->requiredAuthorBookCount((string) $data['author_id']);
    }

    return $data;
  }
}
