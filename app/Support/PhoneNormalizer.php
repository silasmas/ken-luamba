<?php

namespace App\Support;

/**
 * Normalise et valide les numéros MSISDN congolais (243XXXXXXXXX).
 */
class PhoneNormalizer
{
  /**
   * Normalise une saisie utilisateur en MSISDN 243XXXXXXXXX.
   *
   * @param string|null $value Numéro brut
   * @return string|null Numéro normalisé ou null si vide
   */
  public static function normalize(?string $value): ?string
  {
    if ($value === null || trim($value) === '') {
      return null;
    }

    $digits = preg_replace('/\D+/', '', $value) ?? '';

    if ($digits === '') {
      return null;
    }

    if (str_starts_with($digits, '243')) {
      return substr($digits, 0, 12);
    }

    if (str_starts_with($digits, '0') && strlen($digits) === 10) {
      return '243'.substr($digits, 1);
    }

    if (strlen($digits) === 9) {
      return '243'.$digits;
    }

    return substr($digits, 0, 12);
  }

  /**
   * Indique si un numéro respecte le format 243XXXXXXXXX.
   *
   * @param string|null $value Numéro à vérifier
   * @return bool True si le format est valide
   */
  public static function isValid(?string $value): bool
  {
    if ($value === null || $value === '') {
      return false;
    }

    return (bool) preg_match('/^243[0-9]{9}$/', $value);
  }
}
