<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle représentant la progression de lecture d'un lien de partage.
 */
class DigitalAccessShareProgress extends Model
{
  use HasUuids;

  /**
   * Nom de la table associée.
   *
   * @var string
   */
  protected $table = 'digital_access_share_progress';

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'digital_access_share_id',
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
   * Lien de partage associé.
   *
   * @return BelongsTo<DigitalAccessShare, $this>
   */
  public function share(): BelongsTo
  {
    return $this->belongsTo(DigitalAccessShare::class, 'digital_access_share_id');
  }
}
