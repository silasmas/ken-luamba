<?php

namespace App\Console\Commands;

use App\Services\Books\BookDashboardExportService;
use Database\Seeders\Support\BookDashboardExportData;
use Illuminate\Console\Command;

/**
 * Exporte les données books.ts en JSON numérotés pour le dashboard.
 */
class ExportBookDashboardDataCommand extends Command
{
  protected $signature = 'books:export-dashboard-data {slug? : Slug optionnel}';

  protected $description = 'Exporte les livres books.ts (ordre, pages, textes) pour import dashboard';

  /**
   * Génère les fichiers JSON dans database/seeders/exports/books/.
   *
   * @param BookDashboardExportService $exportService Service d'export
   * @return int Code de sortie
   */
  public function handle(BookDashboardExportService $exportService): int
  {
    $slug = $this->argument('slug');

    if (is_string($slug) && $slug !== '') {
      $book = BookDashboardExportData::forSlug($slug);

      if ($book === null) {
        $this->error('Livre introuvable : '.$slug);

        return self::FAILURE;
      }

      $path = $exportService->exportBook($book);
      $this->info('Export : '.$path);
    } else {
      $paths = $exportService->exportAll();

      foreach ($paths as $path) {
        $this->line('Export : '.$path);
      }
    }

    $this->info('Dossier : '.$exportService->absoluteExportDirectory());

    return self::SUCCESS;
  }
}
