<?php

namespace App\Services;

use App\Enums\BookFormatType;
use App\Enums\DiscountAppliesTo;
use App\Enums\DiscountType;
use App\Models\Book;
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
    $itemCount = $cart->items->sum('quantity');
    $distinctPhysicalBookCount = $this->countDistinctPhysicalBooks($cart);
    $maxSingleTitleQuantity = $this->maxSinglePhysicalTitleQuantity($cart);

    $specialModes = [
      DiscountAppliesTo::AuthorCompleteSet->value,
      DiscountAppliesTo::DistinctPhysicalBooks->value,
      DiscountAppliesTo::SinglePhysicalTitle->value,
    ];

    $discount = QuantityDiscount::query()
      ->where('is_active', true)
      ->where(function ($query) use (
        $physicalCount,
        $itemCount,
        $distinctPhysicalBookCount,
        $maxSingleTitleQuantity,
        $specialModes,
      ): void {
        $query
          ->where(function ($authorQuery) use ($distinctPhysicalBookCount): void {
            $authorQuery
              ->where('applies_to', DiscountAppliesTo::AuthorCompleteSet->value)
              ->where('min_quantity', '<=', $distinctPhysicalBookCount);
          })
          ->orWhere(function ($distinctQuery) use ($distinctPhysicalBookCount): void {
            $distinctQuery
              ->where('applies_to', DiscountAppliesTo::DistinctPhysicalBooks->value)
              ->where('min_quantity', '<=', $distinctPhysicalBookCount);
          })
          ->orWhere(function ($singleTitleQuery) use ($maxSingleTitleQuantity): void {
            $singleTitleQuery
              ->where('applies_to', DiscountAppliesTo::SinglePhysicalTitle->value)
              ->where('min_quantity', '<=', $maxSingleTitleQuantity);
          })
          ->orWhere(function ($defaultQuery) use ($physicalCount, $itemCount, $specialModes): void {
            $defaultQuery
              ->whereNotIn('applies_to', $specialModes)
              ->where('min_quantity', '<=', max($physicalCount, $itemCount));
          });
      })
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
   * Retourne les livres physiques publiés requis pour une collection complète.
   *
   * @param string $authorId Identifiant de l'auteur
   * @return list<string> Identifiants des livres requis
   */
  public function requiredAuthorBookIds(string $authorId): array
  {
    return Book::query()
      ->published()
      ->where('author_id', $authorId)
      ->whereHas('formats', function ($query): void {
        $query->whereIn('type', [
          BookFormatType::Hardcover->value,
          BookFormatType::Paperback->value,
        ]);
      })
      ->pluck('id')
      ->all();
  }

  /**
   * Compte les titres physiques publiés d'un auteur éligibles à la remise collection.
   *
   * @param string $authorId Identifiant de l'auteur
   * @return int Nombre de titres requis
   */
  public function requiredAuthorBookCount(string $authorId): int
  {
    return count($this->requiredAuthorBookIds($authorId));
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
   * Compte les titres physiques distincts présents dans le panier.
   *
   * @param Cart $cart Panier cible
   * @return int Nombre de livres différents
   */
  private function countDistinctPhysicalBooks(Cart $cart): int
  {
    return $cart->items
      ->filter(fn ($item) => ! $item->bookFormat->type->isDigital())
      ->pluck('bookFormat.book_id')
      ->unique()
      ->count();
  }

  /**
   * Retourne la plus grande quantité d'un même titre physique dans le panier.
   *
   * @param Cart $cart Panier cible
   * @return int Quantité maximale sur un seul livre
   */
  private function maxSinglePhysicalTitleQuantity(Cart $cart): int
  {
    $max = $cart->items
      ->filter(fn ($item) => ! $item->bookFormat->type->isDigital())
      ->groupBy(fn ($item) => $item->bookFormat->book_id)
      ->map(fn ($items) => $items->sum('quantity'))
      ->max();

    return (int) ($max ?? 0);
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
    if ($rule->applies_to === DiscountAppliesTo::AuthorCompleteSet) {
      return $this->authorCompleteSetApplies($rule, $cart);
    }

    $quantityBase = match ($rule->applies_to) {
      DiscountAppliesTo::PhysicalOnly => $physicalCount,
      DiscountAppliesTo::SinglePhysicalTitle => $this->maxSinglePhysicalTitleQuantity($cart),
      DiscountAppliesTo::DistinctPhysicalBooks => $this->countDistinctPhysicalBooks($cart),
      DiscountAppliesTo::SpecificBook => $cart->items
        ->filter(fn ($item) => $item->bookFormat->book_id === $rule->book_id)
        ->sum('quantity'),
      DiscountAppliesTo::AllBooks => $cart->items->sum('quantity'),
      default => 0,
    };

    return $quantityBase >= $rule->min_quantity;
  }

  /**
   * Vérifie si le panier contient au moins un exemplaire de chaque livre physique de l'auteur.
   *
   * @param QuantityDiscount $rule Règle collection complète
   * @param Cart $cart Panier courant
   * @return bool True si la collection est complète
   */
  private function authorCompleteSetApplies(QuantityDiscount $rule, Cart $cart): bool
  {
    if ($rule->author_id === null) {
      return false;
    }

    $requiredBookIds = $this->requiredAuthorBookIds($rule->author_id);

    if ($requiredBookIds === []) {
      return false;
    }

    $cartBookIds = $cart->items
      ->filter(fn ($item) => ! $item->bookFormat->type->isDigital() && $item->quantity >= 1)
      ->map(fn ($item) => $item->bookFormat->book_id)
      ->unique()
      ->values()
      ->all();

    foreach ($requiredBookIds as $bookId) {
      if (! in_array($bookId, $cartBookIds, true)) {
        return false;
      }
    }

    return true;
  }
}
