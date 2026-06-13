<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ville nationale couverte par la politique de livraison.
 */
class ShippingCity extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'name',
    'is_delivery_available',
    'sort_order',
  ];

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'is_delivery_available' => 'boolean',
    ];
  }

  /**
   * Zones tarifaires rattachées à cette ville.
   *
   * @return HasMany<ShippingZone, $this>
   */
  public function zones(): HasMany
  {
    return $this->hasMany(ShippingZone::class);
  }
}
