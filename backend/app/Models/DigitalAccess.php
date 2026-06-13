<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle représentant un droit d'accès à un contenu numérique.
 */
class DigitalAccess extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'user_id',
    'order_id',
    'order_item_id',
    'book_format_id',
    'is_active',
    'granted_at',
    'expires_at',
    'download_count',
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
      'granted_at' => 'datetime',
      'expires_at' => 'datetime',
    ];
  }

  /**
   * Client propriétaire de l'accès.
   *
   * @return BelongsTo<User, $this>
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Commande source.
   *
   * @return BelongsTo<Order, $this>
   */
  public function order(): BelongsTo
  {
    return $this->belongsTo(Order::class);
  }

  /**
   * Ligne de commande associée.
   *
   * @return BelongsTo<OrderItem, $this>
   */
  public function orderItem(): BelongsTo
  {
    return $this->belongsTo(OrderItem::class);
  }

  /**
   * Format numérique accessible.
   *
   * @return BelongsTo<BookFormat, $this>
   */
  public function bookFormat(): BelongsTo
  {
    return $this->belongsTo(BookFormat::class);
  }

  /**
   * Historique des consultations.
   *
   * @return HasMany<DigitalAccessLog, $this>
   */
  public function logs(): HasMany
  {
    return $this->hasMany(DigitalAccessLog::class);
  }
}
