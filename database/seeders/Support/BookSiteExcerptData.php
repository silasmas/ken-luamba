<?php

namespace Database\Seeders\Support;

/**
 * Extraits feuilletables — délègue à BookSiteData (books.ts).
 */
class BookSiteExcerptData
{
  /**
   * Retourne les pages d'aperçu pour un slug de livre.
   *
   * @param string $slug Identifiant URL du livre
   * @return list<array<string, mixed>> Pages de l'extrait
   */
  public static function forSlug(string $slug): array
  {
    $book = BookSiteData::forSlug($slug);
    $excerpt = is_array($book['excerpt'] ?? null) ? $book['excerpt'] : [];

    if ($excerpt !== []) {
      return $excerpt;
    }

    return [
      ['kind' => 'cover'],
      ['kind' => 'backCover'],
    ];
  }
}
