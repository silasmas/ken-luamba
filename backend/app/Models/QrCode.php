<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle représentant un QR code unique par commande.
 */
class QrCode extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'order_id',
    'token',
    'is_used',
    'used_at',
  ];

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'is_used' => 'boolean',
      'used_at' => 'datetime',
    ];
  }

  /**
   * Commande associée au QR code.
   *
   * @return BelongsTo<Order, $this>
   */
  public function order(): BelongsTo
  {
    return $this->belongsTo(Order::class);
  }
}
