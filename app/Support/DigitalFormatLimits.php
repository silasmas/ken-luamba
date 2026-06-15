<?php

namespace App\Support;

use App\Models\BookFormat;

/**
 * Résout les limites d'accès numérique (globales ou par format).
 */
class DigitalFormatLimits
{
  /**
   * Retourne le nombre max de téléchargements pour un format.
   *
   * @param BookFormat|null $format Format numérique
   * @return int Limite appliquée
   */
  public static function maxDownloads(?BookFormat $format): int
  {
    $configured = $format?->digital_max_downloads;

    if (is_int($configured) && $configured > 0) {
      return $configured;
    }

    return max(1, (int) config('digital.max_downloads', 5));
  }

  /**
   * Retourne la durée de validité des liens de lecture en minutes.
   *
   * @param BookFormat|null $format Format numérique
   * @return int Durée en minutes
   */
  public static function streamExpiryMinutes(?BookFormat $format): int
  {
    $configured = $format?->digital_stream_expiry_minutes;

    if (is_int($configured) && $configured > 0) {
      return $configured;
    }

    return max(1, (int) config('digital.stream_expiry_minutes', 120));
  }

  /**
   * Indique si le partage par lien est activé pour un format.
   *
   * @param BookFormat|null $format Format numérique
   * @return bool True si le partage est autorisé
   */
  public static function sharingEnabled(?BookFormat $format): bool
  {
    return (bool) ($format?->digital_share_enabled ?? false);
  }

  /**
   * Retourne la durée de validité de l'URL du lien partagé (depuis la création).
   *
   * @param BookFormat|null $format Format numérique
   * @return int Durée en minutes
   */
  public static function shareLinkExpiryMinutes(?BookFormat $format): int
  {
    $configured = $format?->digital_share_expiry_minutes;

    if (is_int($configured) && $configured > 0) {
      return $configured;
    }

    return max(1, (int) config('digital.share_expiry_minutes', 2880));
  }

  /**
   * Retourne la durée de lecture accessible après ouverture du lien.
   *
   * @param BookFormat|null $format Format numérique
   * @return int Durée en minutes
   */
  public static function shareReadingMinutes(?BookFormat $format): int
  {
    $configured = $format?->digital_share_reading_minutes;

    if (is_int($configured) && $configured > 0) {
      return $configured;
    }

    return max(1, (int) config('digital.share_reading_minutes', 90));
  }

  /**
   * Alias de compatibilité pour la validité du lien partagé.
   *
   * @param BookFormat|null $format Format numérique
   * @return int Durée en minutes
   */
  public static function shareExpiryMinutes(?BookFormat $format): int
  {
    return self::shareLinkExpiryMinutes($format);
  }

  /**
   * Retourne le nombre max de liens de partage actifs par accès.
   *
   * @param BookFormat|null $format Format numérique
   * @return int Limite appliquée
   */
  public static function shareMaxLinks(?BookFormat $format): int
  {
    $configured = $format?->digital_share_max_links;

    if (is_int($configured) && $configured > 0) {
      return $configured;
    }

    return max(1, (int) config('digital.share_max_links', 3));
  }

  /**
   * Formate une durée en minutes pour affichage humain.
   *
   * @param int $minutes Durée en minutes
   * @return string Libellé lisible (ex. 90 min, 2 h 30 min)
   */
  public static function formatMinutesLabel(int $minutes): string
  {
    $minutes = max(1, $minutes);

    if ($minutes < 60) {
      return $minutes.' min';
    }

    $hours = intdiv($minutes, 60);
    $rest = $minutes % 60;

    if ($rest === 0) {
      return $hours.' h';
    }

    return $hours.' h '.$rest.' min';
  }
}
