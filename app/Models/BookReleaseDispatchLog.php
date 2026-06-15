<?php

namespace App\Models;

use App\Enums\BookReleaseDispatchStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Journal d'envoi d'une alerte sortie à un inscrit.
 */
class BookReleaseDispatchLog extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'book_id',
    'book_release_subscription_id',
    'recipient_email',
    'message_id',
    'subject',
    'body',
    'status',
    'scheduled_for',
    'sent_at',
    'error_message',
  ];

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'status' => BookReleaseDispatchStatus::class,
      'scheduled_for' => 'datetime',
      'sent_at' => 'datetime',
    ];
  }

  /**
   * Livre concerné.
   *
   * @return BelongsTo<Book, $this>
   */
  public function book(): BelongsTo
  {
    return $this->belongsTo(Book::class);
  }

  /**
   * Inscription alerte liée.
   *
   * @return BelongsTo<BookReleaseSubscription, $this>
   */
  public function subscription(): BelongsTo
  {
    return $this->belongsTo(BookReleaseSubscription::class, 'book_release_subscription_id');
  }
}
