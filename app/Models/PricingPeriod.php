<?php

namespace App\Models;

use App\Enums\PricingPeriodType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle représentant une période tarifaire (pré-commande, vente, promo).
 */
class PricingPeriod extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'book_format_id',
    'label',
    'type',
    'price',
    'currency',
    'start_at',
    'end_at',
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
      'type' => PricingPeriodType::class,
      'price' => 'decimal:2',
      'start_at' => 'datetime',
      'end_at' => 'datetime',
      'is_active' => 'boolean',
    ];
  }

  /**
   * Format de livre associé à cette période.
   *
   * @return BelongsTo<BookFormat, $this>
   */
  public function bookFormat(): BelongsTo
  {
    return $this->belongsTo(BookFormat::class);
  }

  /**
   * Indique si la période est active à l'instant donné.
   *
   * @return bool True si la période couvre la date actuelle
   */
  public function isCurrentlyActive(): bool
  {
    if (! $this->is_active) {
      return false;
    }

    $now = now();

    return $now->between($this->start_at, $this->end_at);
  }
}
