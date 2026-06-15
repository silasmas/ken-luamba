<?php

namespace Database\Seeders\Support;

use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Génère des fichiers numériques de démonstration pour les seeders.
 */
class SeederDigitalFileService
{
  /**
   * Crée un EPUB minimal valide sur le disque local.
   *
   * @param string $destinationPath Chemin relatif sur le disque local
   * @param string $title Titre du livre
   * @return string Chemin relatif publié
   */
  public function generateDemoEpub(string $destinationPath, string $title): string
  {
    $directory = dirname($destinationPath);

    if ($directory !== '.') {
      Storage::disk('local')->makeDirectory($directory);
    }

    $absolutePath = Storage::disk('local')->path($destinationPath);
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $zip = new ZipArchive();
    $zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('mimetype', 'application/epub+zip');
    $zip->setCompressionName('mimetype', ZipArchive::CM_STORE);
    $zip->addFromString(
      'META-INF/container.xml',
      '<?xml version="1.0" encoding="UTF-8"?>'
      .'<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">'
      .'<rootfiles><rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml"/></rootfiles>'
      .'</container>',
    );
    $zip->addFromString(
      'OEBPS/content.opf',
      '<?xml version="1.0" encoding="UTF-8"?>'
      .'<package xmlns="http://www.idpf.org/2007/opf" version="3.0" unique-identifier="uid">'
      .'<metadata xmlns:dc="http://purl.org/dc/elements/1.1/">'
      .'<dc:identifier id="uid">demo-epub</dc:identifier>'
      .'<dc:title>'.$safeTitle.'</dc:title>'
      .'<dc:language>fr</dc:language>'
      .'</metadata>'
      .'<manifest>'
      .'<item id="chapter1" href="chapter1.xhtml" media-type="application/xhtml+xml"/>'
      .'</manifest>'
      .'<spine><itemref idref="chapter1"/></spine>'
      .'</package>',
    );
    $zip->addFromString(
      'OEBPS/chapter1.xhtml',
      '<?xml version="1.0" encoding="UTF-8"?>'
      .'<html xmlns="http://www.w3.org/1999/xhtml"><head><title>'.$safeTitle.'</title></head>'
      .'<body><h1>'.$safeTitle.'</h1>'
      .'<p>Extrait de démonstration Ken Luamba Éditions.</p>'
      .'<p>Ce fichier permet de tester la lecture EPUB dans l\'espace membre.</p>'
      .'</body></html>',
    );
    $zip->close();

    return $destinationPath;
  }

  /**
   * Crée un fichier MP3 minimal lisible par les navigateurs.
   *
   * @param string $destinationPath Chemin relatif sur le disque local
   * @return string Chemin relatif publié
   */
  public function generateDemoMp3(string $destinationPath): string
  {
    $directory = dirname($destinationPath);

    if ($directory !== '.') {
      Storage::disk('local')->makeDirectory($directory);
    }

    $assetPath = database_path('seeders/assets/digital/demo-audio.mp3');

    if (is_file($assetPath)) {
      Storage::disk('local')->put($destinationPath, file_get_contents($assetPath) ?: '');

      return $destinationPath;
    }

    $hex = '4944330300000000000000000000000000000000'
      .'fff348c400000000000000000000000000000000000000000000000000000000000000000000'
      .'fff348c400000000000000000000000000000000000000000000000000000000000000000000';

    $binary = hex2bin($hex);

    if ($binary === false || strlen($binary) < 128) {
      $binary = str_repeat("\0", 2048);
    }

    Storage::disk('local')->put($destinationPath, $binary);

    return $destinationPath;
  }
}
