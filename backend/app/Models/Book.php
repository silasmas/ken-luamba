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
