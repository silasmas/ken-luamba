<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Commune rattachée à une zone de livraison.
 */
class ShippingZoneCommune extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'shipping_zone_id',
    'name',
    'city',
  ];

  /**
   * Zone parente de la commune.
   *
   * @return BelongsTo<ShippingZone, $this>
   */
  public function zone(): BelongsTo
  {
    return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
  }
}
