<?php

namespace App\Enums;

/**
 * Types de formats disponibles pour un livre.
 */
enum BookFormatType: string
{
  case Hardcover = 'hardcover';
  case Paperback = 'paperback';
  case Ebook = 'ebook';
  case Audiobook = 'audiobook';

  /**
   * Libellé affiché dans l'interface admin.
   *
   * @return string Libellé du format
   */
  public function label(): string
  {
    return match ($this) {
      self::Hardcover => 'Livre relié (couverture rigide)',
      self::Paperback => 'Broché',
      self::Ebook => 'Ebook',
      self::Audiobook => 'Audio',
    };
  }

  /**
   * Indique si le format est numérique (sans stock physique).
   *
   * @return bool True si ebook ou audio
   */
  public function isDigital(): bool
  {
    return match ($this) {
      self::Ebook, self::Audiobook => true,
      default => false,
    };
  }
}
