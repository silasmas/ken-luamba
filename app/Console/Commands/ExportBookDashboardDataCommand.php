<?php

namespace App\Console\Commands;

use App\Services\Books\BookDashboardExportService;
use Illuminate\Console\Command;

/**
 * Exporte les fiches livres en JSON pour import dashboard production.
 */
class ExportBookDashboardDataCommand extends Command
{
  /**
   * Signature de la commande Artisan.
   *
   * @var string
   */
  protected $signature = 'books:export-dashboard-data {slug? : Slug optionnel d\'un seul livre}';

  /**
   * Description affichée dans la liste des commandes.
   *
   * @var string
   */
  protected $description = 'Exporte les données livres (pages, textes, visuels) pour import manuel en production';

  /**
   * Génère les fichiers JSON d'export.
   *
   * @param BookDashboardExportService $exportService Service d'export
   * @return int Code de sortie
   */
  public function handle(BookDashboardExportService $exportService): int
  {
    $slug = $this->argument('slug');

    if (is_string($slug) && $slug !== '') {
      $book = \Database\Seeders\Support\BookDashboardExportData::forSlug($slug);

      if ($book === null) {
        $this->error('Livre introuvable : '.$slug);

        return self::FAILURE;
      }

      $path = $exportService->exportBook($slug, $book);
      $this->info('Export : storage/app/private/'.$path);
    } else {
      $paths = $exportService->exportAll();

      foreach ($paths as $path) {
        $this->line('Export : storage/app/private/'.$path);
      }

      $this->info('Guide : storage/app/private/'.BookDashboardExportService::EXPORT_DIRECTORY.'/README-import-production.md');
    }

    $this->info('Dossier : '.$exportService->absoluteExportDirectory());

    return self::SUCCESS;
  }
}
