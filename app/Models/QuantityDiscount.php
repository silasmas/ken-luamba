<?php

namespace App\Models;

use App\Enums\DiscountAppliesTo;
use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle représentant une remise par quantité de livres.
 */
class QuantityDiscount extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'name',
    'min_quantity',
    'discount_type',
    'discount_value',
    'applies_to',
    'book_id',
    'author_id',
    'stackable',
    'valid_from',
    'valid_until',
    'is_active',
  ];

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'discount_type' => DiscountType::class,
      'applies_to' => DiscountAppliesTo::class,
      'discount_value' => 'decimal:2',
      'stackable' => 'boolean',
      'valid_from' => 'datetime',
      'valid_until' => 'datetime',
      'is_active' => 'boolean',
    ];
  }

  /**
   * Livre ciblé si la remise est spécifique.
   *
   * @return BelongsTo<Book, $this>
   */
  public function book(): BelongsTo
  {
    return $this->belongsTo(Book::class);
  }

  /**
   * Auteur ciblé si la remise exige sa collection complète.
   *
   * @return BelongsTo<Author, $this>
   */
  public function author(): BelongsTo
  {
    return $this->belongsTo(Author::class);
  }
}
