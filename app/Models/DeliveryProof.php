<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle représentant une preuve photo de livraison.
 */
class DeliveryProof extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'delivery_id',
    'uploaded_by',
    'photo_path',
    'comment',
  ];

  /**
   * Livraison associée.
   *
   * @return BelongsTo<Delivery, $this>
   */
  public function delivery(): BelongsTo
  {
    return $this->belongsTo(Delivery::class);
  }

  /**
   * Utilisateur ayant uploadé la preuve.
   *
   * @return BelongsTo<User, $this>
   */
  public function uploader(): BelongsTo
  {
    return $this->belongsTo(User::class, 'uploaded_by');
  }
}
