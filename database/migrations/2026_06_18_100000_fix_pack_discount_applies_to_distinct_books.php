<?php

use App\Enums\DiscountAppliesTo;
use App\Models\QuantityDiscount;
use Illuminate\Database\Migrations\Migration;

/**
 * Corrige les remises « pack N livres » pour compter les titres distincts.
 */
return new class extends Migration
{
  /**
   * Bascule les règles pack vers le mode livres physiques différents.
   *
   * @return void
   */
  public function up(): void
  {
    QuantityDiscount::query()
      ->where(function ($query): void {
        $query
          ->where('name', 'like', '%Pack 4%')
          ->orWhere('name', 'like', '%pack 4%')
          ->orWhere('name', 'like', '%4 livres%');
      })
      ->whereIn('applies_to', [
        DiscountAppliesTo::PhysicalOnly->value,
        DiscountAppliesTo::AllBooks->value,
      ])
      ->update([
        'applies_to' => DiscountAppliesTo::DistinctPhysicalBooks->value,
      ]);
  }

  /**
   * Ne restaure pas automatiquement l'ancienne portée.
   *
   * @return void
   */
  public function down(): void
  {
  }
};
