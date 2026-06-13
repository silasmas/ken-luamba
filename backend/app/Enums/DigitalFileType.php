<?php

namespace App\Enums;

/**
 * Types de fichiers numériques téléchargeables (ebook / audio).
 */
enum DigitalFileType: string
{
  case Pdf = 'pdf';
  case Epub = 'epub';
  case Mp3 = 'mp3';

  /**
   * Libellé affiché dans l'admin et côté client.
   *
   * @return string Libellé du type de fichier
   */
  public function label(): string
  {
    return match ($this) {
      self::Pdf => 'PDF',
      self::Epub => 'EPUB',
      self::Mp3 => 'MP3 (audio)',
    };
  }

  /**
   * Types MIME acceptés à l'upload pour ce format.
   *
   * @return list<string> Types MIME autorisés
   */
  public function mimeTypes(): array
  {
    return match ($this) {
      self::Pdf => ['application/pdf'],
      self::Epub => ['application/epub+zip', 'application/epub'],
      self::Mp3 => ['audio/mpeg', 'audio/mp3'],
    };
  }

  /**
   * Extensions de fichier autorisées.
   *
   * @return list<string> Extensions sans point
   */
  public function extensions(): array
  {
    return match ($this) {
      self::Pdf => ['pdf'],
      self::Epub => ['epub'],
      self::Mp3 => ['mp3'],
    };
  }

  /**
   * Types de fichier disponibles selon le format produit.
   *
   * @param BookFormatType $formatType Type de format livre
   * @return list<self> Types autorisés
   */
  public static function forBookFormat(BookFormatType $formatType): array
  {
    return match ($formatType) {
      BookFormatType::Ebook => [self::Pdf, self::Epub],
      BookFormatType::Audiobook => [self::Mp3],
      default => [],
    };
  }
}
