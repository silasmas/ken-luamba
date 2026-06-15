<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Inscription e-mail pour être prévenu de la sortie d'un livre.
 */
class BookReleaseSubscription extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'book_id',
    'email',
  ];

  /**
   * Livre concerné par l'alerte.
   *
   * @return BelongsTo<Book, $this>
   */
  public function book(): BelongsTo
  {
    return $this->belongsTo(Book::class);
  }
}
