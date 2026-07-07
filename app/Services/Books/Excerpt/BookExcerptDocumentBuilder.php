<?php

namespace App\Services\Books\Excerpt;

use App\Models\Book;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Prépare les pages d'extrait et le HTML aligné sur l'aperçu public.
 */
class BookExcerptDocumentBuilder
{
  /**
   * Retourne les pages normalisées prêtes à l'export.
   *
   * @param Book $book Livre source
   * @param bool $includeCovers Inclure couverture et quatrième de couverture
   * @return list<array<string, mixed>> Pages d'extrait
   */
  public function resolvePages(Book $book, bool $includeCovers): array
  {
    $pages = is_array($book->excerpt) ? $book->excerpt : [];

    if ($pages === []) {
      throw ValidationException::withMessages([
        'excerpt' => ['Aucune page d\'aperçu feuilletable à exporter.'],
      ]);
    }

    if (! $includeCovers) {
      $filtered = array_values(array_filter(
        $pages,
        fn (mixed $page): bool => is_array($page)
          && ! in_array((string) ($page['kind'] ?? ''), ['cover', 'backCover'], true),
      ));

      if ($filtered === []) {
        throw ValidationException::withMessages([
          'excerpt' => ['Aucune page de contenu à exporter sans les couvertures.'],
        ]);
      }

      return $filtered;
    }

    $normalized = array_values(array_filter($pages, fn (mixed $page): bool => is_array($page)));

    $hasCover = collect($normalized)->contains(
      fn (array $page): bool => ($page['kind'] ?? '') === 'cover',
    );
    $hasBackCover = collect($normalized)->contains(
      fn (array $page): bool => ($page['kind'] ?? '') === 'backCover',
    );

    if (! $hasCover && filled($book->cover_image)) {
      array_unshift($normalized, ['kind' => 'cover']);
    }

    if (! $hasBackCover && filled($book->back_cover_image)) {
      $normalized[] = ['kind' => 'backCover'];
    }

    if ($normalized === []) {
      throw ValidationException::withMessages([
        'excerpt' => ['Aucune page exportable avec les options choisies.'],
      ]);
    }

    return $normalized;
  }

  /**
   * Construit un document HTML complet pour PDF ou conversion.
   *
   * @param Book $book Livre source
   * @param bool $includeCovers Inclure les pages couverture
   * @return string HTML UTF-8
   */
  public function buildHtmlDocument(Book $book, bool $includeCovers): string
  {
    $pages = $this->resolvePages($book, $includeCovers);
    $title = htmlspecialchars($book->title, ENT_QUOTES, 'UTF-8');
    $coverPath = $this->resolveImagePath($book->cover_image);
    $backCoverPath = $this->resolveImagePath($book->back_cover_image);
    $body = '';

    foreach ($pages as $index => $page) {
      $body .= $this->renderPageHtml($page, $title, $coverPath, $backCoverPath, $index + 1);
    }

    return '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>'
      .$title.' — Extrait</title><style>'.$this->stylesheet().'</style></head><body>'
      .$body.'</body></html>';
  }

  /**
   * Feuille de style proche du lecteur public feuilletable.
   *
   * @return string CSS embarqué
   */
  public function stylesheet(): string
  {
    return <<<'CSS'
@page { margin: 16mm 14mm; }
body { font-family: DejaVu Sans, sans-serif; color: #1a1a1a; background: #fbfaf6; font-size: 11pt; line-height: 1.62; }
.page { page-break-after: always; min-height: 250mm; position: relative; background: #fbfaf6; padding: 14mm 12mm; box-sizing: border-box; }
.page:last-child { page-break-after: auto; }
.page-image { padding: 0; background: #1a1a1a; }
.page-image img { width: 100%; height: 255mm; object-fit: cover; display: block; }
.page-chapter { text-align: center; padding-top: 70mm; }
.eyebrow { font-size: 8pt; letter-spacing: 0.24em; text-transform: uppercase; color: rgba(26,26,26,0.38); }
.eyebrow-center { margin-bottom: 8mm; }
.divider { width: 12mm; height: 1px; background: rgba(26,26,26,0.18); margin: 5mm 0 7mm; }
.divider-center { margin-left: auto; margin-right: auto; }
.title-display { font-size: 18pt; font-weight: bold; line-height: 1.12; margin: 0 0 6mm; }
.title-chapter { font-size: 22pt; font-weight: bold; line-height: 1.08; margin-top: 6mm; }
.title-text { font-size: 14pt; font-weight: bold; margin: 0 0 5mm; }
.paragraphs { margin-top: 4mm; }
.paragraphs p { margin: 0 0 4mm; font-size: 10.5pt; line-height: 1.62; color: rgba(26,26,26,0.72); }
.paragraphs-quote p { font-family: DejaVu Serif, serif; font-size: 11pt; line-height: 1.7; color: rgba(26,26,26,0.8); }
.footer { position: absolute; bottom: 10mm; left: 12mm; right: 12mm; display: flex; justify-content: space-between; font-size: 7.5pt; letter-spacing: 0.18em; text-transform: uppercase; color: rgba(26,26,26,0.35); }
CSS;
  }

  /**
   * Rend une page d'extrait en fragment HTML.
   *
   * @param array<string, mixed> $page Données de page
   * @param string $bookTitle Titre échappé
   * @param string|null $coverPath Chemin absolu couverture
   * @param string|null $backCoverPath Chemin absolu verso
   * @param int $fallbackNumber Numéro de page de secours
   * @return string Fragment HTML
   */
  public function renderPageHtml(
    array $page,
    string $bookTitle,
    ?string $coverPath,
    ?string $backCoverPath,
    int $fallbackNumber,
  ): string {
    $kind = (string) ($page['kind'] ?? 'text');

    if ($kind === 'cover') {
      return $this->renderImagePageHtml($coverPath, $bookTitle, 'Couverture');
    }

    if ($kind === 'backCover') {
      return $this->renderImagePageHtml($backCoverPath, $bookTitle, 'Quatrième de couverture');
    }

    if ($kind === 'chapter') {
      $chapter = htmlspecialchars((string) ($page['chapter'] ?? ''), ENT_QUOTES, 'UTF-8');
      $title = htmlspecialchars((string) ($page['title'] ?? ''), ENT_QUOTES, 'UTF-8');

      return '<div class="page page-chapter">'
        .'<div class="eyebrow eyebrow-center">'.$chapter.'</div>'
        .'<div class="divider divider-center"></div>'
        .'<div class="title-chapter">'.$title.'</div>'
        .'</div>';
    }

    $eyebrow = htmlspecialchars((string) ($page['eyebrow'] ?? ''), ENT_QUOTES, 'UTF-8');
    $title = htmlspecialchars((string) ($page['title'] ?? ''), ENT_QUOTES, 'UTF-8');
    $paragraphs = is_array($page['paragraphs'] ?? null) ? $page['paragraphs'] : [];
    $pageLabel = htmlspecialchars(
      (string) ($page['pageLabel'] ?? (string) $fallbackNumber),
      ENT_QUOTES,
      'UTF-8',
    );
    $quoteClass = $kind === 'text' ? ' paragraphs-quote' : '';
    $footerLeft = $kind === 'part' ? 'Parties du livre' : ($kind === 'section' ? 'Synthèse' : 'Ken Luamba');

    $html = '<div class="page">';
    if ($eyebrow !== '') {
      $html .= '<div class="eyebrow">'.$eyebrow.'</div><div class="divider"></div>';
    }
    if ($title !== '') {
      $html .= '<div class="title-display">'.$title.'</div>';
    }
    $html .= '<div class="paragraphs'.$quoteClass.'">';
    foreach ($paragraphs as $paragraph) {
      $html .= '<p>'.htmlspecialchars((string) $paragraph, ENT_QUOTES, 'UTF-8').'</p>';
    }
    $html .= '</div>';
    $html .= '<div class="footer"><span>'.$footerLeft.'</span><span>'.$pageLabel.'</span></div>';
    $html .= '</div>';

    return $html;
  }

  /**
   * Rend une page image (couverture ou verso).
   *
   * @param string|null $imagePath Chemin absolu image
   * @param string $bookTitle Titre échappé
   * @param string $fallbackLabel Libellé si image absente
   * @return string Fragment HTML
   */
  private function renderImagePageHtml(?string $imagePath, string $bookTitle, string $fallbackLabel): string
  {
    if ($imagePath !== null) {
      return '<div class="page page-image"><img src="'.$this->imageDataUri($imagePath).'" alt="'
        .htmlspecialchars($fallbackLabel, ENT_QUOTES, 'UTF-8').'" /></div>';
    }

    return '<div class="page page-chapter"><div class="title-chapter">'.$bookTitle.'</div>'
      .'<p style="text-align:center;color:rgba(26,26,26,0.5);margin-top:8mm;">'
      .htmlspecialchars($fallbackLabel, ENT_QUOTES, 'UTF-8').'</p></div>';
  }

  /**
   * Résout le chemin absolu d'une image stockée sur le disque public.
   *
   * @param string|null $relativePath Chemin relatif storage
   * @return string|null Chemin absolu ou null
   */
  public function resolveImagePath(?string $relativePath): ?string
  {
    if ($relativePath === null || $relativePath === '') {
      return null;
    }

    $disk = Storage::disk('public');

    if (! $disk->exists($relativePath)) {
      return null;
    }

    return $disk->path($relativePath);
  }

  /**
   * Encode une image locale en data URI pour Dompdf.
   *
   * @param string $absolutePath Chemin absolu
   * @return string Data URI
   */
  public function imageDataUri(string $absolutePath): string
  {
    $mime = match (strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION))) {
      'png' => 'image/png',
      'webp' => 'image/webp',
      default => 'image/jpeg',
    };

    return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($absolutePath));
  }
}
