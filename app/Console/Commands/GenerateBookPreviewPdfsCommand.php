<?php

namespace App\Console\Commands;

use App\Services\Books\BookPreviewPdfService;
use Database\Seeders\Support\BookDashboardExportData;
use Illuminate\Console\Command;

/**
 * Génère les PDF d'extrait de test pour le lecteur feuilletable (mode secours).
 */
class GenerateBookPreviewPdfsCommand extends Command
{
  /**
   * Signature de la commande Artisan.
   *
   * @var string
   */
  protected $signature = 'books:generate-preview-pdfs {slug? : Slug optionnel d\'un seul livre}';

  /**
   * Description affichée dans la liste des commandes.
   *
   * @var string
   */
  protected $description = 'Génère un PDF d\'aperçu par livre pour tester le lecteur PDF (option 2)';

  /**
   * Génère les PDF et affiche les chemins publics.
   *
   * @param BookPreviewPdfService $pdfService Service de génération PDF
   * @return int Code de sortie
   */
  public function handle(BookPreviewPdfService $pdfService): int
  {
    $slug = $this->argument('slug');

    try {
      if (is_string($slug) && $slug !== '') {
        $book = BookDashboardExportData::forSlug($slug);

        if ($book === null) {
          $this->error('Livre introuvable : '.$slug);

          return self::FAILURE;
        }

        $path = $pdfService->generateForBook($slug, $book);
        $this->info('PDF : storage/app/public/'.$path);
        $this->line('URL : /storage/'.$path);
      } else {
        $paths = $pdfService->generateAll();

        foreach ($paths as $path) {
          $this->line('PDF : storage/app/public/'.$path.' → /storage/'.$path);
        }
      }
    } catch (\Throwable $exception) {
      $this->error($exception->getMessage());

      return self::FAILURE;
    }

    $this->newLine();
    $this->comment('Pour tester le mode PDF : vider le Repeater « Aperçu feuilletable » et uploader le PDF dans « Extrait PDF ».');

    return self::SUCCESS;
  }
}
