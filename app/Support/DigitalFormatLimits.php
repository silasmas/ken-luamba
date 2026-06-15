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
   * Retourne la durée de validité des liens de lecture en heures.
   *
   * @param BookFormat|null $format Format numérique
   * @return int Durée en heures
   */
  public static function streamExpiryHours(?BookFormat $format): int
  {
    $configured = $format?->digital_stream_expiry_hours;

    if (is_int($configured) && $configured > 0) {
      return $configured;
    }

    return max(1, (int) config('digital.stream_expiry_hours', 2));
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
   * Retourne la durée de validité d'un lien de partage en heures.
   *
   * @param BookFormat|null $format Format numérique
   * @return int Durée en heures
   */
  public static function shareExpiryHours(?BookFormat $format): int
  {
    $configured = $format?->digital_share_expiry_hours;

    if (is_int($configured) && $configured > 0) {
      return $configured;
    }

    return max(1, (int) config('digital.share_expiry_hours', 48));
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
}
