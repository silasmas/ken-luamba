<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Zone de livraison nationale avec tarif associé.
 */
class ShippingZone extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'shipping_city_id',
    'name',
    'amount',
    'currency',
    'sort_order',
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
      'amount' => 'decimal:2',
      'is_active' => 'boolean',
    ];
  }

  /**
   * Ville parente de la zone.
   *
   * @return BelongsTo<ShippingCity, $this>
   */
  public function city(): BelongsTo
  {
    return $this->belongsTo(ShippingCity::class, 'shipping_city_id');
  }

  /**
   * Communes couvertes par cette zone.
   *
   * @return HasMany<ShippingZoneCommune, $this>
   */
  public function communes(): HasMany
  {
    return $this->hasMany(ShippingZoneCommune::class);
  }
}
