<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle représentant un auteur (profil public).
 */
class Author extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'full_name',
    'slug',
    'title',
    'roles',
    'short_bio',
    'full_bio',
    'profile_image',
    'cover_image',
    'home_hero_primary_image',
    'home_hero_overlay_image',
    'home_section_primary_image',
    'home_section_overlay_image',
    'page_primary_image',
    'page_overlay_image',
    'social_links',
    'featured_quote',
    'is_primary',
    'is_published',
    'meta_title',
    'meta_description',
  ];

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'social_links' => 'array',
      'roles' => 'array',
      'is_primary' => 'boolean',
      'is_published' => 'boolean',
    ];
  }

  /**
   * Livres publiés par cet auteur.
   *
   * @return HasMany<Book, $this>
   */
  public function books(): HasMany
  {
    return $this->hasMany(Book::class);
  }

  /**
   * Filtre les auteurs publiés.
   *
   * @param \Illuminate\Database\Eloquent\Builder<Author> $query Requête Eloquent
   * @return \Illuminate\Database\Eloquent\Builder<Author>
   */
  public function scopePublished($query)
  {
    return $query->where('is_published', true);
  }
}
