<?php

namespace App\Models;

use App\Enums\BookReviewStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Témoignage et note d'un lecteur sur un livre.
 */
class BookReview extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'book_id',
    'user_id',
    'author_role',
    'rating',
    'content',
    'status',
    'moderated_at',
    'moderated_by',
  ];

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'rating' => 'integer',
      'status' => BookReviewStatus::class,
      'moderated_at' => 'datetime',
    ];
  }

  /**
   * Livre commenté.
   *
   * @return BelongsTo<Book, $this>
   */
  public function book(): BelongsTo
  {
    return $this->belongsTo(Book::class);
  }

  /**
   * Lecteur auteur du témoignage.
   *
   * @return BelongsTo<User, $this>
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Administrateur ayant modéré l'avis.
   *
   * @return BelongsTo<User, $this>
   */
  public function moderator(): BelongsTo
  {
    return $this->belongsTo(User::class, 'moderated_by');
  }

  /**
   * Filtre les avis approuvés.
   *
   * @param \Illuminate\Database\Eloquent\Builder<BookReview> $query Requête Eloquent
   * @return \Illuminate\Database\Eloquent\Builder<BookReview>
   */
  public function scopeApproved($query)
  {
    return $query->where('status', BookReviewStatus::Approved);
  }
}
