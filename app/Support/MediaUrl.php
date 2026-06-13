<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Utilitaires pour les URLs de fichiers publics.
 */
class MediaUrl
{
  /**
   * Retourne l'URL publique d'un fichier stocké.
   *
   * @param string|null $path Chemin relatif du fichier
   * @return string|null URL complète ou null
   */
  public static function fromPath(?string $path): ?string
  {
    if ($path === null || $path === '') {
      return null;
    }

    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
      return $path;
    }

    $publicDisk = Storage::disk('public');
    $privateDisk = Storage::disk('local');

    if (! $publicDisk->exists($path) && $privateDisk->exists($path)) {
      $directory = dirname($path);

      if ($directory !== '.') {
        $publicDisk->makeDirectory($directory);
      }

      $publicDisk->put($path, $privateDisk->get($path));
    }

    if (! $publicDisk->exists($path)) {
      return null;
    }

    return asset('storage/'.$path);
  }
}
