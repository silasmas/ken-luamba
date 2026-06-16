<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
    'first_opened_at',
    'reading_expires_at',
    'reading_seconds_budget',
    'reading_seconds_consumed',
    'reading_timer_active_at',
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
      'first_opened_at' => 'datetime',
      'reading_expires_at' => 'datetime',
      'reading_timer_active_at' => 'datetime',
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
   * Progression de lecture enregistrée pour ce lien.
   *
   * @return HasOne<DigitalAccessShareProgress, $this>
   */
  public function progress(): HasOne
  {
    return $this->hasOne(DigitalAccessShareProgress::class, 'digital_access_share_id');
  }

  /**
   * Filtre les liens dont l'URL est encore valide (non révoqués, non expirés).
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
   * Indique si l'URL du lien est encore valide.
   *
   * @return bool True si le lien n'a pas expiré
   */
  public function isLinkValid(): bool
  {
    return $this->revoked_at === null && $this->expires_at->isFuture();
  }

  /**
   * Indique si une session de lecture a déjà démarré.
   *
   * @return bool True si le lien a été ouvert
   */
  public function hasReadingStarted(): bool
  {
    return $this->first_opened_at !== null;
  }

  /**
   * Indique si la session de lecture est encore active.
   *
   * @return bool True si du temps de lecture reste disponible
   */
  public function isReadingActive(): bool
  {
    return $this->hasReadingStarted() && $this->readingSecondsRemaining() > 0;
  }

  /**
   * Indique si le contenu peut encore être lu.
   *
   * @return bool True si lecture autorisée
   */
  public function canRead(): bool
  {
    if (! $this->isLinkValid()) {
      return false;
    }

    if (! $this->hasReadingStarted()) {
      return true;
    }

    return $this->isReadingActive();
  }

  /**
   * Indique si le lien est encore utilisable côté propriétaire.
   *
   * @return bool True si actif
   */
  public function isActive(): bool
  {
    return $this->isLinkValid();
  }

  /**
   * Retourne les secondes restantes avant expiration du lien URL.
   *
   * @return int Secondes restantes (0 si expiré)
   */
  public function linkSecondsRemaining(): int
  {
    if (! $this->expires_at instanceof Carbon) {
      return 0;
    }

    return max(0, now()->diffInSeconds($this->expires_at, false));
  }

  /**
   * Retourne les secondes restantes de la session de lecture effective.
   *
   * @return int Secondes restantes (0 si non démarrée ou expirée)
   */
  public function readingSecondsRemaining(): int
  {
    if ($this->reading_seconds_budget !== null) {
      $consumed = (int) ($this->reading_seconds_consumed ?? 0);

      if ($this->reading_timer_active_at instanceof Carbon) {
        $consumed += max(0, now()->diffInSeconds($this->reading_timer_active_at, false));
      }

      return max(0, (int) $this->reading_seconds_budget - $consumed);
    }

    if (! $this->reading_expires_at instanceof Carbon) {
      return 0;
    }

    return max(0, now()->diffInSeconds($this->reading_expires_at, false));
  }

  /**
   * Retourne les secondes restantes avant expiration globale (compatibilité).
   *
   * @return int Secondes restantes
   */
  public function secondsRemaining(): int
  {
    if ($this->hasReadingStarted()) {
      return $this->readingSecondsRemaining();
    }

    return $this->linkSecondsRemaining();
  }
}
