<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Copie ou génère les médias de démonstration vers le disque public.
 */
class SeederMediaService
{
  /**
   * Publie un fichier source vers le storage public Laravel.
   *
   * @param string $sourcePath Chemin absolu du fichier source
   * @param string $destinationPath Chemin relatif sur le disque public
   * @return string|null Chemin relatif publié ou null si échec
   */
  public function publishFile(string $sourcePath, string $destinationPath): ?string
  {
    if (! File::exists($sourcePath)) {
      return null;
    }

    $directory = dirname($destinationPath);

    if ($directory !== '.') {
      Storage::disk('public')->makeDirectory($directory);
    }

    Storage::disk('public')->put($destinationPath, File::get($sourcePath));

    return $destinationPath;
  }

  /**
   * Retourne le dossier images embarqué avec les seeders du backend.
   *
   * @return string Chemin absolu
   */
  public function bundledImagesDirectory(): string
  {
    return database_path('seeders'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'images');
  }

  /**
   * Retourne le dossier images du projet book-site (frère du monorepo).
   *
   * @return string Chemin absolu
   */
  public function bookSiteImagesDirectory(): string
  {
    $candidates = [
      $this->bundledImagesDirectory(),
      dirname(dirname(base_path())).DIRECTORY_SEPARATOR.'ken-luamba-book-site'
        .DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'images',
      dirname(base_path()).DIRECTORY_SEPARATOR.'frontend'
        .DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'images',
    ];

    foreach ($candidates as $path) {
      if (File::isDirectory($path)) {
        return $path;
      }
    }

    return $this->bundledImagesDirectory();
  }

  /**
   * Génère une couverture JPEG minimaliste pour les livres sans visuel source.
   *
   * @param string $destinationPath Chemin relatif sur le disque public
   * @param string $title Titre affiché
   * @param string $accent Couleur hex de fond
   * @return string Chemin relatif publié
   */
  public function generateCoverPlaceholder(
    string $destinationPath,
    string $title,
    string $accent = '#1b1f2a',
  ): string {
    $directory = dirname($destinationPath);

    if ($directory !== '.') {
      Storage::disk('public')->makeDirectory($directory);
    }

    $absolutePath = Storage::disk('public')->path($destinationPath);
    $width = 800;
    $height = 1200;

    $image = imagecreatetruecolor($width, $height);
    [$red, $green, $blue] = $this->hexToRgb($accent);
    $background = imagecolorallocate($image, $red, $green, $blue);
    imagefilledrectangle($image, 0, 0, $width, $height, $background);

    $textColor = imagecolorallocate($image, 245, 245, 240);
    $wrappedTitle = wordwrap($title, 18, "\n");
    $lines = explode("\n", $wrappedTitle);
    $lineHeight = 42;
    $startY = (int) (($height - (count($lines) * $lineHeight)) / 2);

    foreach ($lines as $index => $line) {
      $x = 60;
      $y = $startY + ($index * $lineHeight);
      imagestring($image, 5, $x, $y, $line, $textColor);
    }

    imagestring($image, 3, 60, $height - 80, 'Ken Luamba', $textColor);
    imagejpeg($image, $absolutePath, 90);
    imagedestroy($image);

    return $destinationPath;
  }

  /**
   * Convertit une couleur hex en composantes RGB.
   *
   * @param string $hex Couleur hex (#RRGGBB)
   * @return array{0:int,1:int,2:int} Composantes RGB
   */
  private function hexToRgb(string $hex): array
  {
    $normalized = ltrim($hex, '#');

    if (strlen($normalized) === 3) {
      $normalized = $normalized[0].$normalized[0]
        .$normalized[1].$normalized[1]
        .$normalized[2].$normalized[2];
    }

    return [
      hexdec(substr($normalized, 0, 2)),
      hexdec(substr($normalized, 2, 2)),
      hexdec(substr($normalized, 4, 2)),
    ];
  }
}
