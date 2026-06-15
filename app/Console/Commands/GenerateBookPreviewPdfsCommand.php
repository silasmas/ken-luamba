<?php

namespace App\Console\Commands;

use App\Services\Books\BookPreviewPdfService;
use Database\Seeders\Support\BookDashboardExportData;
use Illuminate\Console\Command;

/**
 * Génère un PDF d'extrait uploadable par livre (books.ts).
 */
class GenerateBookPreviewPdfsCommand extends Command
{
  protected $signature = 'books:generate-preview-pdfs {slug? : Slug optionnel}';

  protected $description = 'Génère les PDF d\'extrait (couvertures + pages) prêts à uploader';

  /**
   * Génère les PDF dans database/seeders/exports/books/ et storage public.
   *
   * @param BookPreviewPdfService $pdfService Service PDF
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

        $paths = $pdfService->generateForBook($book);
        $this->info('PDF dépôt : '.$paths['repo']);
        $this->line('PDF public : storage/app/public/'.$paths['public']);
      } else {
        $paths = $pdfService->generateAll();

        foreach ($paths as $item) {
          $this->line('PDF dépôt : '.$item['repo']);
          $this->line('PDF public : storage/app/public/'.$item['public']);
        }
      }
    } catch (\Throwable $exception) {
      $this->error($exception->getMessage());

      return self::FAILURE;
    }

    $this->newLine();
    $this->comment('Uploader le fichier *-extrait.pdf dans Admin → Livres → Extrait PDF.');

    return self::SUCCESS;
  }
}
