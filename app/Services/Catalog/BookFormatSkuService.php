<?php

namespace App\Services\Catalog;

use App\Enums\BookFormatType;
use App\Models\Book;
use App\Models\BookFormat;

/**
 * Génère des références SKU uniques pour les formats de livre.
 */
class BookFormatSkuService
{
  /**
   * Mots de liaison ignorés lors de l'abréviation du titre.
   *
   * @var list<string>
   */
  private const STOP_WORDS = [
    'le', 'la', 'les', 'l', 'du', 'de', 'des', 'un', 'une', 'aux', 'au', 'a', 'et', 'en',
  ];

  /**
   * Préfixe commun des références catalogue.
   */
  private const PREFIX = 'KL';

  /**
   * Produit un SKU unique pour un format donné.
   *
   * @param BookFormat $format Format en cours de création
   * @return string Référence unique (ex. KL-EG-HC)
   */
  public function generate(BookFormat $format): string
  {
    $book = $format->book ?? Book::query()->find($format->book_id);
    $type = $format->type instanceof BookFormatType
      ? $format->type
      : BookFormatType::from((string) $format->type);

    if ($book === null) {
      return $this->ensureUnique($this->fallbackSku($type));
    }

    $base = self::PREFIX.'-'.$this->bookCodeFromSlug($book->slug).'-'.$type->skuSuffix();

    return $this->ensureUnique($base);
  }

  /**
   * Dérive un code livre court à partir du slug URL.
   *
   * @param string $slug Slug du livre
   * @return string Code sur 2 lettres majuscules
   */
  public function bookCodeFromSlug(string $slug): string
  {
    $parts = array_values(array_filter(
      explode('-', strtolower($slug)),
      fn (string $part): bool => $part !== '' && ! in_array($part, self::STOP_WORDS, true),
    ));

    if ($parts === []) {
      $alphanumeric = preg_replace('/[^a-z]/', '', strtolower($slug)) ?? '';

      return strtoupper(substr($alphanumeric, 0, 2)) ?: 'BK';
    }

    if (strlen($parts[0]) >= 2) {
      return strtoupper(substr($parts[0], 0, 2));
    }

    $second = $parts[1] ?? $parts[0];

    return strtoupper(substr($parts[0], 0, 1).substr($second, 0, 1));
  }

  /**
   * Garantit l'unicité du SKU en ajoutant un suffixe numérique si besoin.
   *
   * @param string $base Référence de base
   * @return string Référence unique en base
   */
  private function ensureUnique(string $base): string
  {
    $sku = $base;
    $suffix = 2;

    while (BookFormat::query()->where('sku', $sku)->exists()) {
      $sku = $base.'-'.$suffix;
      $suffix++;
    }

    return $sku;
  }

  /**
   * SKU de secours lorsque le livre parent est introuvable.
   *
   * @param BookFormatType $type Type de format
   * @return string Référence temporaire
   */
  private function fallbackSku(BookFormatType $type): string
  {
    return self::PREFIX.'-BK-'.$type->skuSuffix();
  }
}
