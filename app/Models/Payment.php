<?php

namespace App\Models;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle représentant une transaction de paiement.
 */
class Payment extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'order_id',
    'provider',
    'provider_reference',
    'amount',
    'currency',
    'status',
    'channel',
    'phone',
    'metadata',
    'paid_at',
  ];

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'status' => PaymentStatus::class,
      'channel' => PaymentChannel::class,
      'amount' => 'decimal:2',
      'metadata' => 'array',
      'paid_at' => 'datetime',
    ];
  }

  /**
   * Commande liée au paiement.
   *
   * @return BelongsTo<Order, $this>
   */
  public function order(): BelongsTo
  {
    return $this->belongsTo(Order::class);
  }
}
