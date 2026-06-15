<?php

namespace Database\Seeders\Support;

/**
 * Transforme BookSiteData en fiches prêtes pour l'import dashboard Filament.
 */
class BookDashboardExportData
{
  /**
   * Retourne toutes les fiches livres indexées par slug.
   *
   * @return array<string, array<string, mixed>>
   */
  public static function books(): array
  {
    $books = [];

    foreach (BookSiteData::books() as $book) {
      $slug = (string) $book['slug'];
      $books[$slug] = self::fromBookSite($book);
    }

    return $books;
  }

  /**
   * Retourne une fiche par slug.
   *
   * @param string $slug Identifiant URL
   * @return array<string, mixed>|null
   */
  public static function forSlug(string $slug): ?array
  {
    $book = BookSiteData::forSlug($slug);

    return $book !== null ? self::fromBookSite($book) : null;
  }

  /**
   * Retourne les livres ordonnés comme dans books.ts.
   *
   * @return list<array<string, mixed>>
   */
  public static function orderedBooks(): array
  {
    return array_values(array_map(
      fn (array $book): array => self::fromBookSite($book),
      BookSiteData::books(),
    ));
  }

  /**
   * Construit le nom de fichier export (ordre + slug).
   *
   * @param array<string, mixed> $book Fiche export
   * @return string Préfixe fichier (ex. 01-eglise-face-aux-defis-de-lheure)
   */
  public static function exportFilePrefix(array $book): string
  {
    $order = str_pad((string) ($book['order'] ?? 0), 2, '0', STR_PAD_LEFT);
    $slug = (string) ($book['slug'] ?? 'livre');

    return $order.'-'.$slug;
  }

  /**
   * Convertit une entrée BookSiteData en fiche dashboard.
   *
   * @param array<string, mixed> $book Données book-site
   * @return array<string, mixed> Fiche structurée pour l'admin
   */
  public static function fromBookSite(array $book): array
  {
    $slug = (string) $book['slug'];
    $order = (int) ($book['order'] ?? 0);
    $themes = is_array($book['themes'] ?? null) ? $book['themes'] : [];
    $about = is_array($book['about'] ?? null) ? $book['about'] : [];
    $excerpt = is_array($book['excerpt'] ?? null) ? $book['excerpt'] : [];
    $campaign = is_array($book['campaign'] ?? null) ? $book['campaign'] : null;
    $coverFile = BookSiteData::imageFileName($book['cover'] ?? null);
    $backCoverFile = BookSiteData::imageFileName($book['backCover'] ?? null);

    $numberedPages = [];

    foreach ($excerpt as $index => $page) {
      if (! is_array($page)) {
        continue;
      }

      $numberedPages[] = [
        'pageNumber' => $index + 1,
        ...$page,
        'paragraphsText' => isset($page['paragraphs']) && is_array($page['paragraphs'])
          ? implode("\n\n", $page['paragraphs'])
          : null,
      ];
    }

    $bonuses = is_array($campaign['bonus'] ?? null) ? $campaign['bonus'] : [];

    return [
      'order' => $order,
      'slug' => $slug,
      'source' => 'ken-luamba-book-site/src/data/books.ts',
      'importGuide' => [
        'etape1' => 'Admin → Livres → Créer / Modifier',
        'etape2' => 'Coller les champs de sectionContenu et sectionFicheEditoriale',
        'etape3' => 'Uploader cover_image et back_cover_image (fichiers sectionVisuels)',
        'etape4' => 'Repeater Aperçu feuilletable : une entrée par page de excerptPages',
        'etape5' => 'Uploader le PDF {prefix}-extrait.pdf dans « Extrait PDF » pour tester le lecteur PDF',
      ],
      'bookSite' => [
        'status' => $book['status'] ?? null,
        'featured' => (bool) ($book['featured'] ?? false),
        'formats' => $book['formats'] ?? [],
        'reviews' => $book['reviews'] ?? [],
        'campaign' => $campaign,
      ],
      'sectionIdentification' => [
        'title' => $book['title'] ?? '',
        'slug' => $slug,
        'subtitle' => $book['subtitle'] ?? '',
        'tagline' => $book['tagline'] ?? '',
        'is_featured' => (bool) ($book['featured'] ?? false),
        'sort_order' => $order,
        'is_published' => true,
      ],
      'sectionContenu' => [
        'description' => $book['summary'] ?? '',
        'author_note' => null,
        'about_paragraphs' => $about,
        'about_paragraphs_text' => implode("\n\n", $about),
        'themes' => $themes,
        'themes_text' => implode("\n", $themes),
      ],
      'sectionFicheEditoriale' => [
        'page_count' => $book['pages'] ?? null,
        'reading_time' => $book['readingTime'] ?? null,
        'language' => $book['language'] ?? 'Français',
        'release_date' => $book['releaseDate'] ?? null,
        'accent_color' => $book['accent'] ?? '#1b1f2a',
        'category' => $book['category'] ?? '',
        'preorder_campaign_goal' => $campaign['goal'] ?? null,
        'preorder_reserved_count' => $campaign['reserved'] ?? 0,
        'preorder_campaign_bonuses' => $bonuses,
        'preorder_campaign_bonuses_text' => implode("\n", $bonuses),
      ],
      'sectionVisuels' => [
        'cover_image_source' => $coverFile,
        'back_cover_image_source' => $backCoverFile,
        'cover_image_path_book_site' => $book['cover'] ?? null,
        'back_cover_image_path_book_site' => $book['backCover'] ?? null,
        'images_directory' => 'ken-luamba-book-site/public/images',
        'preview_pdf_upload' => 'books/previews/'.$slug.'.pdf',
        'preview_pdf_export' => self::exportFilePrefix(['order' => $order, 'slug' => $slug]).'-extrait.pdf',
      ],
      'excerptPages' => $numberedPages,
      'excerptPageCount' => count($numberedPages),
    ];
  }
}
