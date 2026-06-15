<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle représentant un ouvrage du catalogue.
 */
class Book extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'author_id',
    'title',
    'subtitle',
    'tagline',
    'category',
    'page_count',
    'reading_time_minutes',
    'language',
    'release_date',
    'themes',
    'about_paragraphs',
    'excerpt',
    'accent_color',
    'preorder_campaign_goal',
    'preorder_reserved_count',
    'preorder_campaign_bonuses',
    'release_notification_messages',
    'release_auto_notify_enabled',
    'release_auto_notify_at',
    'release_auto_notify_message_id',
    'release_auto_notify_email_subject',
    'release_auto_notify_email_body',
    'release_auto_notify_sent_at',
    'slug',
    'description',
    'author_note',
    'cover_image',
    'back_cover_image',
    'preview_pdf_path',
    'is_published',
    'is_featured',
    'sort_order',
    'published_at',
  ];

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'is_published' => 'boolean',
      'is_featured' => 'boolean',
      'published_at' => 'datetime',
      'release_date' => 'date',
      'themes' => 'array',
      'about_paragraphs' => 'array',
      'excerpt' => 'array',
      'page_count' => 'integer',
      'reading_time_minutes' => 'integer',
      'preorder_campaign_goal' => 'integer',
      'preorder_reserved_count' => 'integer',
      'preorder_campaign_bonuses' => 'array',
      'release_notification_messages' => 'array',
      'release_auto_notify_enabled' => 'boolean',
      'release_auto_notify_at' => 'datetime',
      'release_auto_notify_sent_at' => 'datetime',
    ];
  }

  /**
   * Réinitialise l'envoi programmé si la planification change.
   */
  protected static function booted(): void
  {
    static::saving(function (Book $book): void {
      if ($book->isDirty([
        'release_auto_notify_enabled',
        'release_auto_notify_at',
        'release_auto_notify_message_id',
        'release_auto_notify_email_subject',
        'release_auto_notify_email_body',
      ])) {
        $book->release_auto_notify_sent_at = null;
      }
    });
  }

  /**
   * Auteur du livre.
   *
   * @return BelongsTo<Author, $this>
   */
  public function author(): BelongsTo
  {
    return $this->belongsTo(Author::class);
  }

  /**
   * Formats disponibles pour ce livre.
   *
   * @return HasMany<BookFormat, $this>
   */
  public function formats(): HasMany
  {
    return $this->hasMany(BookFormat::class);
  }

  /**
   * Remises spécifiques à ce livre.
   *
   * @return HasMany<QuantityDiscount, $this>
   */
  public function quantityDiscounts(): HasMany
  {
    return $this->hasMany(QuantityDiscount::class);
  }

  /**
   * Événements liés à ce livre.
   *
   * @return BelongsToMany<Event, $this>
   */
  public function events(): BelongsToMany
  {
    return $this->belongsToMany(Event::class);
  }

  /**
   * Témoignages lecteurs associés au livre.
   *
   * @return HasMany<BookReview, $this>
   */
  public function reviews(): HasMany
  {
    return $this->hasMany(BookReview::class);
  }

  /**
   * Témoignages lecteurs approuvés.
   *
   * @return HasMany<BookReview, $this>
   */
  public function approvedReviews(): HasMany
  {
    return $this->reviews()->approved();
  }

  /**
   * Filtre les livres publiés.
   *
   * @param \Illuminate\Database\Eloquent\Builder<Book> $query Requête Eloquent
   * @return \Illuminate\Database\Eloquent\Builder<Book>
   */
  public function scopePublished($query)
  {
    return $query->where('is_published', true);
  }
}
