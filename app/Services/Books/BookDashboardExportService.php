<?php

namespace App\Services\Books;

use Database\Seeders\Support\BookDashboardExportData;
use Database\Seeders\Support\SeederMediaService;
use Illuminate\Support\Facades\File;

/**
 * Exporte les fiches livres (books.ts) en JSON prêts pour upload dashboard.
 */
class BookDashboardExportService
{
  /**
   * Dossier d'export dans le dépôt (facile à retrouver / transférer).
   */
  public const REPO_EXPORT_DIRECTORY = 'database/seeders/exports/books';

  /**
   * Exporte tous les livres dans l'ordre books.ts.
   *
   * @return list<string> Chemins absolus des JSON générés
   */
  public function exportAll(): array
  {
    $paths = [];

    foreach (BookDashboardExportData::orderedBooks() as $book) {
      $paths[] = $this->exportBook($book);
    }

    $this->writeReadme();
    $this->writeIndex();

    return $paths;
  }

  /**
   * Exporte un livre vers un fichier JSON numéroté.
   *
   * @param array<string, mixed> $book Fiche export
   * @return string Chemin absolu du fichier
   */
  public function exportBook(array $book): string
  {
    $directory = base_path(self::REPO_EXPORT_DIRECTORY);
    File::ensureDirectoryExists($directory);

    $prefix = BookDashboardExportData::exportFilePrefix($book);
    $absolutePath = $directory.DIRECTORY_SEPARATOR.$prefix.'.json';
    $json = json_encode($book, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '{}';

    File::put($absolutePath, $json);

    return $absolutePath;
  }

  /**
   * Écrit le guide d'import production.
   *
   * @return string Chemin absolu du README
   */
  public function writeReadme(): string
  {
    $directory = base_path(self::REPO_EXPORT_DIRECTORY);
    File::ensureDirectoryExists($directory);
    $path = $directory.DIRECTORY_SEPARATOR.'README-import-production.md';

    $lines = [
      '# Import livres — données books.ts',
      '',
      'Fichiers générés par `php artisan books:export-dashboard-data` dans l\'ordre du book-site.',
      '',
      '| # | Livre | JSON | PDF à uploader |',
      '|---|-------|------|----------------|',
    ];

    foreach (BookDashboardExportData::orderedBooks() as $book) {
      $prefix = BookDashboardExportData::exportFilePrefix($book);
      $title = (string) ($book['sectionIdentification']['title'] ?? $book['slug']);
      $lines[] = sprintf(
        '| %d | %s | `%s.json` | `%s-extrait.pdf` |',
        (int) $book['order'],
        $title,
        $prefix,
        $prefix,
      );
    }

    $lines[] = '';
    $lines[] = '## Dashboard Filament';
    $lines[] = '';
    $lines[] = '1. Ouvrir le JSON du livre';
    $lines[] = '2. Remplir **Identification**, **Contenu**, **Fiche éditoriale**';
    $lines[] = '3. Uploader les images depuis `ken-luamba-book-site/public/images/`';
    $lines[] = '4. Recréer chaque page dans **Aperçu feuilletable** (`excerptPages`)';
    $lines[] = '5. Uploader le PDF `-extrait.pdf` dans **Extrait PDF** pour tester le lecteur PDF';

    File::put($path, implode("\n", $lines)."\n");

    return $path;
  }

  /**
   * Écrit un index JSON de tous les livres.
   *
   * @return string Chemin absolu
   */
  public function writeIndex(): string
  {
    $directory = base_path(self::REPO_EXPORT_DIRECTORY);
    $path = $directory.DIRECTORY_SEPARATOR.'00-index.json';
    $index = [
      'source' => 'ken-luamba-book-site/src/data/books.ts',
      'generatedAt' => now()->toIso8601String(),
      'books' => array_map(
        fn (array $book): array => [
          'order' => $book['order'],
          'slug' => $book['slug'],
          'title' => $book['sectionIdentification']['title'] ?? '',
          'jsonFile' => BookDashboardExportData::exportFilePrefix($book).'.json',
          'pdfFile' => BookDashboardExportData::exportFilePrefix($book).'-extrait.pdf',
          'excerptPageCount' => $book['excerptPageCount'],
        ],
        BookDashboardExportData::orderedBooks(),
      ),
    ];

    File::put($path, json_encode($index, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '{}');

    return $path;
  }

  /**
   * Retourne le chemin absolu du dossier d'export.
   *
   * @return string Chemin absolu
   */
  public function absoluteExportDirectory(): string
  {
    return base_path(self::REPO_EXPORT_DIRECTORY);
  }

  /**
   * Résout le chemin absolu d'une image book-site.
   *
   * @param string|null $fileName Nom de fichier couverture
   * @return string|null Chemin absolu ou null
   */
  public function resolveBookSiteImage(?string $fileName): ?string
  {
    if ($fileName === null || $fileName === '') {
      return null;
    }

    $media = new SeederMediaService();
    $absolutePath = $media->bookSiteImagesDirectory().DIRECTORY_SEPARATOR.$fileName;

    return File::exists($absolutePath) ? $absolutePath : null;
  }
}
