<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle représentant un log d'accès à un contenu numérique.
 */
class DigitalAccessLog extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'digital_access_id',
    'user_id',
    'action',
    'ip_address',
    'user_agent',
    'accessed_at',
  ];

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'accessed_at' => 'datetime',
    ];
  }

  /**
   * Accès numérique consulté.
   *
   * @return BelongsTo<DigitalAccess, $this>
   */
  public function digitalAccess(): BelongsTo
  {
    return $this->belongsTo(DigitalAccess::class);
  }

  /**
   * Utilisateur ayant consulté le contenu.
   *
   * @return BelongsTo<User, $this>
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }
}
