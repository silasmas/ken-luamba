<?php

namespace App\Services;

use App\Enums\DiscountAppliesTo;
use App\Enums\DiscountType;
use App\Models\Cart;
use App\Models\QuantityDiscount;

/**
 * Service de calcul des remises par quantité sur un panier.
 */
class DiscountService
{
  /**
   * Calcule la remise applicable au panier.
   *
   * @param Cart $cart Panier à évaluer
   * @param float $subtotal Sous-total avant remise
   * @return array<string, mixed> Détails de la remise
   */
  public function calculate(Cart $cart, float $subtotal): array
  {
    $cart->loadMissing([
      'items.bookFormat.book',
    ]);

    $physicalCount = $this->countPhysicalItems($cart);

    $discount = QuantityDiscount::query()
      ->where('is_active', true)
      ->where('min_quantity', '<=', max($physicalCount, $cart->items->sum('quantity')))
      ->where(function ($query): void {
        $query->whereNull('valid_from')->orWhere('valid_from', '<=', now());
      })
      ->where(function ($query): void {
        $query->whereNull('valid_until')->orWhere('valid_until', '>=', now());
      })
      ->orderByDesc('min_quantity')
      ->get()
      ->first(fn (QuantityDiscount $rule): bool => $this->ruleApplies($rule, $cart, $physicalCount));

    if ($discount === null) {
      return [
        'rule' => null,
        'amount' => 0.0,
      ];
    }

    $amount = match ($discount->discount_type) {
      DiscountType::Percentage => round($subtotal * ((float) $discount->discount_value / 100), 2),
      DiscountType::FixedAmount => min((float) $discount->discount_value, $subtotal),
    };

    return [
      'rule' => [
        'id' => $discount->id,
        'name' => $discount->name,
        'minQuantity' => $discount->min_quantity,
        'discountType' => $discount->discount_type->value,
        'discountValue' => $discount->discount_value,
      ],
      'amount' => $amount,
    ];
  }

  /**
   * Compte les livres physiques dans le panier.
   *
   * @param Cart $cart Panier cible
   * @return int Nombre d'unités physiques
   */
  private function countPhysicalItems(Cart $cart): int
  {
    return $cart->items
      ->filter(fn ($item) => ! $item->bookFormat->type->isDigital())
      ->sum('quantity');
  }

  /**
   * Vérifie si une règle de remise s'applique au panier.
   *
   * @param QuantityDiscount $rule Règle à tester
   * @param Cart $cart Panier courant
   * @param int $physicalCount Nombre de livres physiques
   * @return bool True si applicable
   */
  private function ruleApplies(QuantityDiscount $rule, Cart $cart, int $physicalCount): bool
  {
    $quantityBase = match ($rule->applies_to) {
      DiscountAppliesTo::PhysicalOnly => $physicalCount,
      DiscountAppliesTo::SpecificBook => $cart->items
        ->filter(fn ($item) => $item->bookFormat->book_id === $rule->book_id)
        ->sum('quantity'),
      DiscountAppliesTo::AllBooks => $cart->items->sum('quantity'),
    };

    return $quantityBase >= $rule->min_quantity;
  }
}
