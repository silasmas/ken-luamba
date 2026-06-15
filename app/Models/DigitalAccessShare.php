<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Modèle représentant un lien de partage temporaire pour un accès numérique.
 */
class DigitalAccessShare extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'digital_access_id',
    'created_by_user_id',
    'token',
    'label',
    'expires_at',
    'revoked_at',
  ];

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'expires_at' => 'datetime',
      'revoked_at' => 'datetime',
    ];
  }

  /**
   * Accès numérique partagé.
   *
   * @return BelongsTo<DigitalAccess, $this>
   */
  public function digitalAccess(): BelongsTo
  {
    return $this->belongsTo(DigitalAccess::class);
  }

  /**
   * Utilisateur ayant créé le lien.
   *
   * @return BelongsTo<User, $this>
   */
  public function createdBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by_user_id');
  }

  /**
   * Filtre les liens encore actifs (non révoqués et non expirés).
   *
   * @param Builder<DigitalAccessShare> $query Requête Eloquent
   * @return Builder<DigitalAccessShare>
   */
  public function scopeActive(Builder $query): Builder
  {
    return $query
      ->whereNull('revoked_at')
      ->where('expires_at', '>', now());
  }

  /**
   * Indique si le lien est encore utilisable.
   *
   * @return bool True si actif
   */
  public function isActive(): bool
  {
    return $this->revoked_at === null && $this->expires_at->isFuture();
  }

  /**
   * Retourne les secondes restantes avant expiration.
   *
   * @return int Secondes restantes (0 si expiré)
   */
  public function secondsRemaining(): int
  {
    if (! $this->expires_at instanceof Carbon) {
      return 0;
    }

    return max(0, now()->diffInSeconds($this->expires_at, false));
  }
}
