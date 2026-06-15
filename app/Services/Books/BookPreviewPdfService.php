<?php

namespace App\Services\Books;

use Database\Seeders\Support\BookDashboardExportData;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Génère des PDF d'extrait uploadables (pages books.ts + couvertures book-site).
 */
class BookPreviewPdfService
{
  /**
   * Dossier d'export PDF dans le dépôt.
   */
  public const REPO_EXPORT_DIRECTORY = 'database/seeders/exports/books';

  public function __construct(
    private readonly BookDashboardExportService $exportService,
  ) {}

  /**
   * Génère les PDF pour tous les livres (dépôt + storage public).
   *
   * @return list<array{repo: string, public: string}> Chemins générés
   */
  public function generateAll(): array
  {
    Storage::disk('public')->makeDirectory('books/previews');
    File::ensureDirectoryExists(base_path(self::REPO_EXPORT_DIRECTORY));

    $paths = [];

    foreach (BookDashboardExportData::orderedBooks() as $book) {
      $paths[] = $this->generateForBook($book);
    }

    return $paths;
  }

  /**
   * Génère le PDF d'un livre.
   *
   * @param array<string, mixed> $book Fiche export
   * @return array{repo: string, public: string} Chemins absolu dépôt + relatif public
   */
  public function generateForBook(array $book): array
  {
    $slug = (string) $book['slug'];
    $prefix = BookDashboardExportData::exportFilePrefix($book);
    $html = $this->buildHtml($book);
    $pdfBinary = $this->renderPdf($html, $book);

    $repoPath = base_path(self::REPO_EXPORT_DIRECTORY).DIRECTORY_SEPARATOR.$prefix.'-extrait.pdf';
    File::put($repoPath, $pdfBinary);

    $publicRelative = 'books/previews/'.$slug.'.pdf';
    Storage::disk('public')->put($publicRelative, $pdfBinary);

    return [
      'repo' => $repoPath,
      'public' => $publicRelative,
    ];
  }

  /**
   * Construit le HTML multi-pages pour le PDF.
   *
   * @param array<string, mixed> $book Données livre
   * @return string Document HTML
   */
  private function buildHtml(array $book): string
  {
    $title = htmlspecialchars((string) ($book['sectionIdentification']['title'] ?? 'Livre'), ENT_QUOTES, 'UTF-8');
    $pages = is_array($book['excerptPages'] ?? null) ? $book['excerptPages'] : [];
    $visuels = is_array($book['sectionVisuels'] ?? null) ? $book['sectionVisuels'] : [];
    $coverPath = $this->exportService->resolveBookSiteImage($visuels['cover_image_source'] ?? null);
    $backPath = $this->exportService->resolveBookSiteImage($visuels['back_cover_image_source'] ?? null);
    $body = '';

    foreach ($pages as $page) {
      if (! is_array($page)) {
        continue;
      }

      $body .= $this->renderPageHtml($page, $title, $coverPath, $backPath);
    }

    return '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">'
      .'<style>'
      .'@page { margin: 18mm 16mm; }'
      .'body { font-family: DejaVu Sans, sans-serif; color: #1b1f2a; font-size: 11pt; line-height: 1.55; }'
      .'.page { page-break-after: always; min-height: 250mm; position: relative; }'
      .'.page:last-child { page-break-after: auto; }'
      .'.eyebrow { font-size: 8pt; letter-spacing: 0.18em; text-transform: uppercase; color: #6b7280; }'
      .'.title { font-size: 20pt; margin: 10mm 0 8mm; font-weight: bold; }'
      .'.chapter-title { font-size: 22pt; text-align: center; margin-top: 70mm; }'
      .'.cover-img { width: 100%; max-height: 255mm; object-fit: contain; }'
      .'.footer { position: absolute; bottom: 0; left: 0; right: 0; font-size: 8pt; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.12em; }'
      .'p { margin: 0 0 4mm; }'
      .'</style></head><body>'.$body.'</body></html>';
  }

  /**
   * Rend une page d'extrait en HTML.
   *
   * @param array<string, mixed> $page Page d'extrait
   * @param string $bookTitle Titre du livre
   * @param string|null $coverPath Chemin absolu couverture
   * @param string|null $backPath Chemin absolu verso
   * @return string Fragment HTML
   */
  private function renderPageHtml(
    array $page,
    string $bookTitle,
    ?string $coverPath,
    ?string $backPath,
  ): string {
    $kind = (string) ($page['kind'] ?? 'text');
    $pageNumber = (int) ($page['pageNumber'] ?? 0);
    $html = '<div class="page">';

    if ($kind === 'cover') {
      if ($coverPath !== null) {
        $html .= '<img class="cover-img" src="'.$this->imageDataUri($coverPath).'" alt="Couverture" />';
      } else {
        $html .= '<div class="chapter-title">'.$bookTitle.'</div>';
      }
    } elseif ($kind === 'backCover') {
      if ($backPath !== null) {
        $html .= '<img class="cover-img" src="'.$this->imageDataUri($backPath).'" alt="Quatrième de couverture" />';
      } else {
        $html .= '<div class="chapter-title">'.$bookTitle.'</div>';
        $html .= '<p style="text-align:center;color:#6b7280;">Quatrième de couverture</p>';
      }
    } elseif ($kind === 'chapter') {
      $chapter = htmlspecialchars((string) ($page['chapter'] ?? ''), ENT_QUOTES, 'UTF-8');
      $title = htmlspecialchars((string) ($page['title'] ?? ''), ENT_QUOTES, 'UTF-8');
      $html .= '<div class="eyebrow">'.$chapter.'</div>';
      $html .= '<div class="chapter-title">'.$title.'</div>';
    } else {
      $eyebrow = htmlspecialchars((string) ($page['eyebrow'] ?? ''), ENT_QUOTES, 'UTF-8');
      $title = htmlspecialchars((string) ($page['title'] ?? ''), ENT_QUOTES, 'UTF-8');

      if ($eyebrow !== '') {
        $html .= '<div class="eyebrow">'.$eyebrow.'</div>';
      }

      if ($title !== '') {
        $html .= '<div class="title">'.$title.'</div>';
      }

      foreach ($page['paragraphs'] ?? [] as $paragraph) {
        $html .= '<p>'.htmlspecialchars((string) $paragraph, ENT_QUOTES, 'UTF-8').'</p>';
      }

      $label = htmlspecialchars((string) ($page['pageLabel'] ?? (string) $pageNumber), ENT_QUOTES, 'UTF-8');
      $html .= '<div class="footer">Ken Luamba · Page '.$label.'</div>';
    }

    $html .= '</div>';

    return $html;
  }

  /**
   * Encode une image locale en data URI pour Dompdf.
   *
   * @param string $absolutePath Chemin absolu du fichier image
   * @return string Data URI base64
   */
  private function imageDataUri(string $absolutePath): string
  {
    $mime = match (strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION))) {
      'png' => 'image/png',
      'webp' => 'image/webp',
      default => 'image/jpeg',
    };

    return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($absolutePath));
  }

  /**
   * Convertit le HTML en binaire PDF via Dompdf.
   *
   * @param string $html Document HTML
   * @param array<string, mixed> $book Données livre (chroot images)
   * @return string Contenu binaire PDF
   */
  private function renderPdf(string $html, array $book): string
  {
    if (! class_exists(\Dompdf\Dompdf::class)) {
      throw new \RuntimeException('Dompdf requis : composer require dompdf/dompdf');
    }

    $media = new \Database\Seeders\Support\SeederMediaService();

    $dompdf = new \Dompdf\Dompdf([
      'isRemoteEnabled' => true,
      'defaultFont' => 'DejaVu Sans',
      'chroot' => [
        base_path(),
        $media->bookSiteImagesDirectory(),
      ],
    ]);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return $dompdf->output();
  }
}
