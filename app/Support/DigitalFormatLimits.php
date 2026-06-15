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
}
