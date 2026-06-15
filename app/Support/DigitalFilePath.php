<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Normalise et valide les chemins de fichiers numériques stockés en base.
 */
class DigitalFilePath
{
  /**
   * Extrait un chemin relatif exploitable depuis la valeur BDD / Filament.
   *
   * @param mixed $storedValue Valeur brute (chaîne, JSON, tableau)
   * @return string|null Chemin relatif sur le disque local
   */
  public static function normalize(mixed $storedValue): ?string
  {
    if ($storedValue === null || $storedValue === '') {
      return null;
    }

    if (is_array($storedValue)) {
      $first = $storedValue[0] ?? null;

      return is_string($first) && $first !== '' ? $first : null;
    }

    if (! is_string($storedValue)) {
      return null;
    }

    $trimmed = trim($storedValue);

    if ($trimmed === '') {
      return null;
    }

    if (str_starts_with($trimmed, '[')) {
      $decoded = json_decode($trimmed, true);

      if (is_array($decoded)) {
        $first = $decoded[0] ?? null;

        return is_string($first) && $first !== '' ? $first : null;
      }
    }

    return $trimmed;
  }

  /**
   * Vérifie qu'un fichier numérique est réellement présent sur le disque local.
   *
   * @param mixed $storedValue Valeur brute en base
   * @return bool True si le fichier existe
   */
  public static function existsOnDisk(mixed $storedValue): bool
  {
    $path = self::normalize($storedValue);

    if ($path === null) {
      return false;
    }

    return Storage::disk('local')->exists($path);
  }
}
