<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle de progression de lecture pour un accès numérique.
 */
class DigitalReadingProgress extends Model
{
  use HasUuids;

  /**
   * Table associée au modèle.
   *
   * @var string
   */
  protected $table = 'digital_reading_progress';

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'digital_access_id',
    'user_id',
    'progress_percent',
    'epub_cfi',
    'audio_position_seconds',
    'audio_duration_seconds',
    'last_opened_at',
  ];

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'last_opened_at' => 'datetime',
    ];
  }

  /**
   * Accès numérique concerné.
   *
   * @return BelongsTo<DigitalAccess, $this>
   */
  public function digitalAccess(): BelongsTo
  {
    return $this->belongsTo(DigitalAccess::class);
  }

  /**
   * Utilisateur propriétaire de la progression.
   *
   * @return BelongsTo<User, $this>
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }
}
