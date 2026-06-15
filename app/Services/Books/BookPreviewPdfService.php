<?php

namespace App\Services\Books;

use Database\Seeders\Support\BookDashboardExportData;
use Illuminate\Support\Facades\Storage;

/**
 * Génère des PDF d'extrait de test à partir des pages feuilletables.
 */
class BookPreviewPdfService
{
  /**
   * Génère les PDF d'aperçu pour tous les livres du catalogue export.
   *
   * @return list<string> Chemins relatifs sur le disque public
   */
  public function generateAll(): array
  {
    Storage::disk('public')->makeDirectory('books/previews');

    $paths = [];

    foreach (BookDashboardExportData::books() as $slug => $book) {
      $paths[] = $this->generateForBook($slug, $book);
    }

    return $paths;
  }

  /**
   * Génère le PDF d'aperçu d'un livre.
   *
   * @param string $slug Identifiant du livre
   * @param array<string, mixed> $book Données export
   * @return string Chemin relatif publié
   */
  public function generateForBook(string $slug, array $book): string
  {
    $relativePath = 'books/previews/'.$slug.'.pdf';
    $html = $this->buildHtml($book);
    $pdfBinary = $this->renderPdf($html);

    Storage::disk('public')->put($relativePath, $pdfBinary);

    return $relativePath;
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
    $body = '';

    foreach ($pages as $page) {
      if (! is_array($page)) {
        continue;
      }

      $body .= $this->renderPageHtml($page, $title);
    }

    return '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">'
      .'<style>'
      .'@page { margin: 28mm 22mm; }'
      .'body { font-family: DejaVu Sans, sans-serif; color: #1b1f2a; font-size: 11pt; line-height: 1.55; }'
      .'.page { page-break-after: always; min-height: 240mm; }'
      .'.page:last-child { page-break-after: auto; }'
      .'.eyebrow { font-size: 8pt; letter-spacing: 0.18em; text-transform: uppercase; color: #6b7280; }'
      .'.title { font-size: 20pt; margin: 12mm 0 8mm; font-weight: bold; }'
      .'.chapter-title { font-size: 22pt; text-align: center; margin-top: 70mm; }'
      .'.cover-label { text-align: center; margin-top: 90mm; font-size: 14pt; color: #6b7280; }'
      .'.footer { margin-top: 18mm; font-size: 8pt; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.12em; }'
      .'p { margin: 0 0 4mm; }'
      .'</style></head><body>'.$body.'</body></html>';
  }

  /**
   * Rend une page d'extrait en HTML.
   *
   * @param array<string, mixed> $page Page d'extrait
   * @param string $bookTitle Titre du livre
   * @return string Fragment HTML
   */
  private function renderPageHtml(array $page, string $bookTitle): string
  {
    $kind = (string) ($page['kind'] ?? 'text');
    $pageNumber = (int) ($page['pageNumber'] ?? 0);
    $html = '<div class="page">';

    if ($kind === 'cover') {
      $html .= '<div class="cover-label">Couverture</div>';
      $html .= '<div class="chapter-title">'.htmlspecialchars($bookTitle, ENT_QUOTES, 'UTF-8').'</div>';
      $html .= '<p style="text-align:center;color:#6b7280;">Image recto à uploader dans l\'admin.</p>';
    } elseif ($kind === 'backCover') {
      $html .= '<div class="cover-label">Quatrième de couverture</div>';
      $html .= '<div class="chapter-title">'.htmlspecialchars($bookTitle, ENT_QUOTES, 'UTF-8').'</div>';
      $html .= '<p style="text-align:center;color:#6b7280;">Image verso à uploader dans l\'admin.</p>';
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
   * Convertit le HTML en binaire PDF via Dompdf si disponible.
   *
   * @param string $html Document HTML
   * @return string Contenu binaire PDF
   */
  private function renderPdf(string $html): string
  {
    if (! class_exists(\Dompdf\Dompdf::class)) {
      throw new \RuntimeException(
        'Dompdf est requis. Exécutez : composer require dompdf/dompdf',
      );
    }

    $dompdf = new \Dompdf\Dompdf([
      'isRemoteEnabled' => false,
      'defaultFont' => 'DejaVu Sans',
    ]);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return $dompdf->output();
  }
}
