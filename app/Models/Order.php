<?php

namespace App\Models;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Modèle représentant une commande client.
 */
class Order extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'order_number',
    'user_id',
    'status',
    'fulfillment_type',
    'pickup_point_id',
    'shipping_address',
    'subtotal',
    'discount_amount',
    'shipping_amount',
    'extra_contribution_amount',
    'total',
    'currency',
    'notes',
    'paid_at',
    'completed_at',
    'payment_reminder_sent_at',
    'admin_pending_delivery_notified_at',
  ];

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'status' => OrderStatus::class,
      'fulfillment_type' => FulfillmentType::class,
      'shipping_address' => 'array',
      'subtotal' => 'decimal:2',
      'discount_amount' => 'decimal:2',
      'shipping_amount' => 'decimal:2',
      'extra_contribution_amount' => 'decimal:2',
      'total' => 'decimal:2',
      'paid_at' => 'datetime',
      'completed_at' => 'datetime',
      'payment_reminder_sent_at' => 'datetime',
      'admin_pending_delivery_notified_at' => 'datetime',
    ];
  }

  /**
   * Client ayant passé la commande.
   *
   * @return BelongsTo<User, $this>
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Point de retrait choisi.
   *
   * @return BelongsTo<PickupPoint, $this>
   */
  public function pickupPoint(): BelongsTo
  {
    return $this->belongsTo(PickupPoint::class);
  }

  /**
   * Lignes de la commande.
   *
   * @return HasMany<OrderItem, $this>
   */
  public function items(): HasMany
  {
    return $this->hasMany(OrderItem::class);
  }

  /**
   * Paiement associé.
   *
   * @return HasOne<Payment, $this>
   */
  public function payment(): HasOne
  {
    return $this->hasOne(Payment::class);
  }

  /**
   * QR code de la commande.
   *
   * @return HasOne<QrCode, $this>
   */
  public function qrCode(): HasOne
  {
    return $this->hasOne(QrCode::class);
  }

  /**
   * Suivi de livraison ou retrait.
   *
   * @return HasOne<Delivery, $this>
   */
  public function delivery(): HasOne
  {
    return $this->hasOne(Delivery::class);
  }

  /**
   * Indique si la commande contient au moins un format physique.
   *
   * @return bool True si relié ou broché présent
   */
  public function hasPhysicalItems(): bool
  {
    $this->loadMissing('items');

    return $this->items->contains(
      fn (OrderItem $item): bool => ! $item->format_type->isDigital(),
    );
  }

  /**
   * Indique si la commande ne contient que des formats numériques.
   *
   * @return bool True si ebook/audio uniquement
   */
  public function isDigitalOnly(): bool
  {
    $this->loadMissing('items');

    if ($this->items->isEmpty()) {
      return false;
    }

    return ! $this->hasPhysicalItems();
  }

  /**
   * Libellé client adapté au type de commande.
   *
   * @return string Statut affiché dans l'espace membre
   */
  public function displayStatusLabel(): string
  {
    if ($this->isDigitalOnly() && $this->status === OrderStatus::Completed) {
      return 'Terminée — Disponible en bibliothèque';
    }

    return $this->status->label();
  }
}
