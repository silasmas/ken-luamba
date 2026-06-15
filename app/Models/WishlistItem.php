<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Favori d'un utilisateur connecté (liste de souhaits).
 */
class WishlistItem extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'user_id',
    'book_id',
  ];

  /**
   * Utilisateur propriétaire du favori.
   *
   * @return BelongsTo<User, $this>
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Livre mis en favori.
   *
   * @return BelongsTo<Book, $this>
   */
  public function book(): BelongsTo
  {
    return $this->belongsTo(Book::class);
  }
}
