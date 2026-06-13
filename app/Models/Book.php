<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
    'slug',
    'description',
    'author_note',
    'cover_image',
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
    ];
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
