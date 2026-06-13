<?php

namespace App\Models;

use App\Enums\OtpType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle représentant un code OTP temporaire.
 */
class OtpCode extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'user_id',
    'email',
    'full_name',
    'code',
    'type',
    'expires_at',
    'used_at',
    'attempts',
  ];

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'type' => OtpType::class,
      'expires_at' => 'datetime',
      'used_at' => 'datetime',
    ];
  }

  /**
   * Utilisateur lié au code OTP.
   *
   * @return BelongsTo<User, $this>
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Indique si le code est encore valide.
   *
   * @return bool True si non utilisé et non expiré
   */
  public function isValid(): bool
  {
    if ($this->used_at !== null) {
      return false;
    }

    return $this->expires_at->isFuture();
  }
}
