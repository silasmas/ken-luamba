<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle représentant un point de retrait physique.
 */
class PickupPoint extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'name',
    'address',
    'city',
    'phone',
    'opening_hours',
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
      'is_active' => 'boolean',
    ];
  }

  /**
   * Commandes associées à ce point de retrait.
   *
   * @return HasMany<Order, $this>
   */
  public function orders(): HasMany
  {
    return $this->hasMany(Order::class);
  }
}
