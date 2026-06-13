<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle représentant le suivi de livraison ou retrait d'une commande.
 */
class Delivery extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'order_id',
    'courier_id',
    'status',
    'assigned_at',
    'delivered_at',
    'notes',
    'stale_assignment_notified_at',
  ];

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'status' => DeliveryStatus::class,
      'assigned_at' => 'datetime',
      'delivered_at' => 'datetime',
      'stale_assignment_notified_at' => 'datetime',
    ];
  }

  /**
   * Commande liée à cette livraison.
   *
   * @return BelongsTo<Order, $this>
   */
  public function order(): BelongsTo
  {
    return $this->belongsTo(Order::class);
  }

  /**
   * Livreur assigné.
   *
   * @return BelongsTo<User, $this>
   */
  public function courier(): BelongsTo
  {
    return $this->belongsTo(User::class, 'courier_id');
  }

  /**
   * Preuves photo de livraison.
   *
   * @return HasMany<DeliveryProof, $this>
   */
  public function proofs(): HasMany
  {
    return $this->hasMany(DeliveryProof::class);
  }
}
