<?php

namespace App\Services\Books;

use App\Models\Book;
use App\Support\MediaUrl;
use Database\Seeders\Support\BookSiteData;
use Database\Seeders\Support\SeederMediaService;
use Illuminate\Support\Facades\File;

/**
 * Résout et publie les couvertures de livres manquantes dans le storage public.
 */
class BookCoverService
{
  /**
   * Initialise le service avec le gestionnaire de médias seeders.
   *
   * @param SeederMediaService $mediaService Service de publication de fichiers
   */
  public function __construct(
    private readonly SeederMediaService $mediaService,
  ) {}

  /**
   * Retourne l'URL publique de la couverture d'un livre.
   *
   * @param Book|null $book Livre cible
   * @return string|null URL absolue ou null
   */
  public function url(?Book $book): ?string
  {
    if ($book === null) {
      return null;
    }

    $existingUrl = MediaUrl::fromPath($book->cover_image);

    if ($existingUrl !== null) {
      return $existingUrl;
    }

    $publishedPath = $this->publishMissingCover($book);

    if ($publishedPath === null) {
      return null;
    }

    return MediaUrl::fromPath($publishedPath);
  }

  /**
   * Publie une couverture absente depuis les assets embarqués ou le book-site.
   *
   * @param Book $book Livre cible
   * @return string|null Chemin relatif publié ou null
   */
  private function publishMissingCover(Book $book): ?string
  {
    $siteBook = BookSiteData::forSlug($book->slug);

    if ($siteBook === null) {
      return null;
    }

    $fileName = BookSiteData::imageFileName($siteBook['cover'] ?? null);

    if ($fileName === null) {
      return null;
    }

    $sourcePath = $this->resolveSourcePath($fileName);

    if ($sourcePath === null) {
      return null;
    }

    $destinationPath = 'books/covers/'.$book->slug.'/'.basename($fileName);
    $publishedPath = $this->mediaService->publishFile($sourcePath, $destinationPath);

    if ($publishedPath === null) {
      return null;
    }

    $book->updateQuietly(['cover_image' => $publishedPath]);

    return $publishedPath;
  }

  /**
   * Localise le fichier source sur le disque (assets seeders ou book-site).
   *
   * @param string $fileName Nom du fichier couverture
   * @return string|null Chemin absolu ou null
   */
  private function resolveSourcePath(string $fileName): ?string
  {
    $candidates = [
      $this->mediaService->bundledImagesDirectory().DIRECTORY_SEPARATOR.$fileName,
      $this->mediaService->bookSiteImagesDirectory().DIRECTORY_SEPARATOR.$fileName,
    ];

    foreach ($candidates as $candidate) {
      if (File::exists($candidate)) {
        return $candidate;
      }
    }

    return null;
  }
}
