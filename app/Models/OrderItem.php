<?php

namespace App\Models;

use App\Enums\BookFormatType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle représentant une ligne de commande.
 */
class OrderItem extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'order_id',
    'book_format_id',
    'book_title',
    'format_type',
    'quantity',
    'unit_price',
    'total_price',
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
      'format_type' => BookFormatType::class,
      'unit_price' => 'decimal:2',
      'total_price' => 'decimal:2',
    ];
  }

  /**
   * Commande parente.
   *
   * @return BelongsTo<Order, $this>
   */
  public function order(): BelongsTo
  {
    return $this->belongsTo(Order::class);
  }

  /**
   * Format de livre commandé.
   *
   * @return BelongsTo<BookFormat, $this>
   */
  public function bookFormat(): BelongsTo
  {
    return $this->belongsTo(BookFormat::class);
  }
}
