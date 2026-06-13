<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle représentant une ligne d'article dans un panier.
 */
class CartItem extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'cart_id',
    'book_format_id',
    'quantity',
    'unit_price',
    'pricing_period_id',
  ];

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'unit_price' => 'decimal:2',
    ];
  }

  /**
   * Panier parent.
   *
   * @return BelongsTo<Cart, $this>
   */
  public function cart(): BelongsTo
  {
    return $this->belongsTo(Cart::class);
  }

  /**
   * Format de livre ajouté au panier.
   *
   * @return BelongsTo<BookFormat, $this>
   */
  public function bookFormat(): BelongsTo
  {
    return $this->belongsTo(BookFormat::class);
  }

  /**
   * Période tarifaire appliquée lors de l'ajout.
   *
   * @return BelongsTo<PricingPeriod, $this>
   */
  public function pricingPeriod(): BelongsTo
  {
    return $this->belongsTo(PricingPeriod::class);
  }

  /**
   * Calcule le total de la ligne.
   *
   * @return float Montant ligne
   */
  public function lineTotal(): float
  {
    return (float) $this->unit_price * $this->quantity;
  }
}
