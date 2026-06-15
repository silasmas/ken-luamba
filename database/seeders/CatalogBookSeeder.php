<?php

namespace Database\Seeders;

use App\Enums\BookFormatType;
use App\Enums\DigitalFileType;
use App\Enums\PricingPeriodType;
use App\Models\Author;
use App\Models\Book;
use App\Models\BookFormat;
use App\Models\PricingPeriod;
use Database\Seeders\Support\BookSiteData;
use Database\Seeders\Support\SeederDigitalFileService;
use Database\Seeders\Support\SeederMediaService;
use Illuminate\Database\Seeder;

/**
 * Alimente le catalogue depuis books.ts (textes + extraits feuilletables).
 * Les couvertures recto/verso sont à uploader manuellement dans l'admin.
 */
class CatalogBookSeeder extends Seeder
{
  /**
   * Exécute le seed du catalogue éditorial.
   */
  public function run(): void
  {
    $author = Author::query()->where('slug', 'ken-luamba')->first();

    if ($author === null) {
      return;
    }

    $this->refreshAuthorProfile();

    Book::query()
      ->where('slug', 'mon-premier-ouvrage')
      ->update(['is_published' => false, 'is_featured' => false]);

    foreach (BookSiteData::books() as $siteBook) {
      $this->seedBook($author, $siteBook);
    }
  }

  /**
   * Met à jour le profil auteur si l'image source est disponible localement.
   *
   * @return void
   */
  private function refreshAuthorProfile(): void
  {
    $author = Author::query()->where('slug', 'ken-luamba')->first();

    if ($author === null) {
      return;
    }

    $media = new SeederMediaService();
    $imagesDir = $media->bookSiteImagesDirectory();
    $authorImage = $media->publishFile(
      $imagesDir.DIRECTORY_SEPARATOR.'pasteur-ken.png',
      'authors/ken-luamba.png',
    );

    if ($authorImage === null) {
      return;
    }

    $author->update([
      'profile_image' => $authorImage,
      'roles' => [
        'Pasteur titulaire du C.M.P.',
        'Master en Théologie',
        'Auteur & conférencier',
      ],
      'short_bio' => 'Pasteur titulaire du Centre Missionnaire Philadelphie et coordinateur des extensions de la Communauté Philadelphie.',
      'full_bio' => 'Pasteur titulaire du Centre Missionnaire Philadelphie, Ken Luamba consacre sa vie à l\'enseignement de la Parole et à la formation d\'une génération de croyants solides. Titulaire d\'un Master en Théologie, il coordonne les extensions de la Communauté Philadelphie. Auteur et conférencier, il écrit pour édifier et préparer une génération aux défis de son temps.',
      'featured_quote' => 'Je n\'écris pas pour distraire une génération. J\'écris pour la préparer.',
    ]);
  }

  /**
   * Crée ou met à jour un livre à partir des données books.ts.
   *
   * @param Author $author Auteur principal
   * @param array<string, mixed> $site Données book-site
   * @return void
   */
  private function seedBook(Author $author, array $site): void
  {
    $slug = (string) $site['slug'];
    $campaign = is_array($site['campaign'] ?? null) ? $site['campaign'] : null;
    $about = is_array($site['about'] ?? null) ? $site['about'] : [];
    $themes = is_array($site['themes'] ?? null) ? $site['themes'] : [];
    $excerpt = is_array($site['excerpt'] ?? null) ? $site['excerpt'] : [];
    $bonuses = is_array($campaign['bonus'] ?? null) ? $campaign['bonus'] : [];

    $book = Book::query()->updateOrCreate(
      ['slug' => $slug],
      [
        'author_id' => $author->id,
        'title' => (string) ($site['title'] ?? ''),
        'subtitle' => (string) ($site['subtitle'] ?? ''),
        'tagline' => (string) ($site['tagline'] ?? ''),
        'category' => (string) ($site['category'] ?? ''),
        'page_count' => (int) ($site['pages'] ?? 0),
        'reading_time_minutes' => BookSiteData::readingTimeMinutes($site['readingTime'] ?? null),
        'language' => (string) ($site['language'] ?? 'Français'),
        'release_date' => (string) ($site['releaseDate'] ?? BookSiteData::PREORDER_RELEASE_DATE),
        'themes' => $themes,
        'about_paragraphs' => $about,
        'excerpt' => $excerpt,
        'accent_color' => (string) ($site['accent'] ?? '#1b1f2a'),
        'preorder_campaign_goal' => $campaign['goal'] ?? null,
        'preorder_reserved_count' => $campaign['reserved'] ?? 0,
        'preorder_campaign_bonuses' => $bonuses,
        'description' => (string) ($site['summary'] ?? ''),
        'author_note' => $about[0] ?? null,
        'is_published' => true,
        'is_featured' => (bool) ($site['featured'] ?? false),
        'sort_order' => (int) ($site['order'] ?? 0),
        'published_at' => now()->subMonths(1),
      ],
    );

    $this->seedFormatsAndPricing($book, $slug);
  }

  /**
   * Crée formats, fichiers numériques démo et tarifs par slug.
   *
   * @param Book $book Livre parent
   * @param string $slug Identifiant URL
   * @return void
   */
  private function seedFormatsAndPricing(Book $book, string $slug): void
  {
    $digitalFiles = new SeederDigitalFileService();

    match ($slug) {
      'eglise-face-aux-defis-de-lheure' => $this->seedEgliseFormats($book, $digitalFiles),
      'le-poids-du-silence' => $this->seedSilenceFormats($book),
      'generation-debout' => $this->seedGenerationFormats($book),
      'eglise-face-a-lesprit-de-babylone' => $this->seedBabyloneFormats($book),
      default => null,
    };
  }

  /**
   * Formats — L'Église face aux défis de l'heure.
   *
   * @param Book $book Livre parent
   * @param SeederDigitalFileService $digitalFiles Générateur fichiers numériques
   * @return void
   */
  private function seedEgliseFormats(Book $book, SeederDigitalFileService $digitalFiles): void
  {
    $hardcover = $this->upsertFormat($book, 'KL-EG-HC', BookFormatType::Hardcover, 120, true);
    $ebook = $this->upsertFormat($book, 'KL-EG-EB', BookFormatType::Ebook, null, true, DigitalFileType::Epub);
    $audio = $this->upsertFormat($book, 'KL-EG-AU', BookFormatType::Audiobook, null, true, DigitalFileType::Mp3);

    $ebook->update([
      'digital_file_path' => $digitalFiles->generateDemoEpub(
        'books/digital/eglise-face-aux-defis-de-lheure.epub',
        $book->title,
      ),
    ]);
    $audio->update([
      'digital_file_path' => $digitalFiles->generateDemoMp3(
        'books/digital/eglise-face-aux-defis-de-lheure.mp3',
      ),
    ]);

    $this->upsertPricing($hardcover->id, 'Pré-commande lancement', PricingPeriodType::Preorder, 30000);
    $this->upsertPricing($ebook->id, 'Ebook lancement', PricingPeriodType::Regular, 14000);
    $this->upsertPricing($audio->id, 'Audio lancement', PricingPeriodType::Regular, 18000);
  }

  /**
   * Formats — Le Prix du Sacrifice.
   *
   * @param Book $book Livre parent
   * @return void
   */
  private function seedSilenceFormats(Book $book): void
  {
    $paperback = $this->upsertFormat($book, 'KL-PS-PB', BookFormatType::Paperback, 80, true);
    $ebook = $this->upsertFormat($book, 'KL-PS-EB', BookFormatType::Ebook, null, true, DigitalFileType::Epub);
    $audio = $this->upsertFormat($book, 'KL-PS-AU', BookFormatType::Audiobook, null, true, DigitalFileType::Mp3);

    $this->upsertPricing($paperback->id, 'Précommande lancement', PricingPeriodType::Preorder, 25000);
    $this->upsertPricing($ebook->id, 'Ebook lancement', PricingPeriodType::Regular, 11000);
    $this->upsertPricing($audio->id, 'Audio lancement', PricingPeriodType::Regular, 15000);
  }

  /**
   * Formats — Les Zones Sombres du Cœur Humain.
   *
   * @param Book $book Livre parent
   * @return void
   */
  private function seedGenerationFormats(Book $book): void
  {
    $paperback = $this->upsertFormat($book, 'KL-GD-PB', BookFormatType::Paperback, 0, true);
    $ebook = $this->upsertFormat($book, 'KL-GD-EB', BookFormatType::Ebook, null, true, DigitalFileType::Epub);
    $audio = $this->upsertFormat($book, 'KL-GD-AU', BookFormatType::Audiobook, null, true, DigitalFileType::Mp3);

    $this->upsertPricing($paperback->id, 'Précommande lancement', PricingPeriodType::Preorder, 22000);
    $this->upsertPricing($ebook->id, 'Ebook lancement', PricingPeriodType::Regular, 13000);
    $this->upsertPricing($audio->id, 'Audio lancement', PricingPeriodType::Regular, 17000);
  }

  /**
   * Formats — L'Église face à l'esprit de Babylone.
   *
   * @param Book $book Livre parent
   * @return void
   */
  private function seedBabyloneFormats(Book $book): void
  {
    $paperback = $this->upsertFormat($book, 'KL-BB-PB', BookFormatType::Paperback, 0, true);
    $ebook = $this->upsertFormat($book, 'KL-BB-EB', BookFormatType::Ebook, null, true, DigitalFileType::Epub);
    $audio = $this->upsertFormat($book, 'KL-BB-AU', BookFormatType::Audiobook, null, true, DigitalFileType::Mp3);

    $this->upsertPricing($paperback->id, 'Précommande lancement', PricingPeriodType::Preorder, 23000);
    $this->upsertPricing($ebook->id, 'Ebook lancement', PricingPeriodType::Regular, 13000);
    $this->upsertPricing($audio->id, 'Audio lancement', PricingPeriodType::Regular, 17000);
  }

  /**
   * Crée ou met à jour un format de livre.
   *
   * @param Book $book Livre parent
   * @param string $sku Référence SKU
   * @param BookFormatType $type Type de format
   * @param int|null $stock Stock physique
   * @param bool $isActive Format actif
   * @param DigitalFileType|null $digitalType Type fichier numérique
   * @return BookFormat Format créé ou mis à jour
   */
  private function upsertFormat(
    Book $book,
    string $sku,
    BookFormatType $type,
    ?int $stock,
    bool $isActive,
    ?DigitalFileType $digitalType = null,
  ): BookFormat {
    return BookFormat::query()->updateOrCreate(
      ['sku' => $sku],
      [
        'book_id' => $book->id,
        'type' => $type,
        'stock_quantity' => $stock,
        'digital_file_type' => $digitalType,
        'is_active' => $isActive,
      ],
    );
  }

  /**
   * Crée ou met à jour une période tarifaire active.
   *
   * @param string $formatId Identifiant format
   * @param string $label Libellé période
   * @param PricingPeriodType $type Type de période
   * @param int $price Prix en CDF
   * @return void
   */
  private function upsertPricing(
    string $formatId,
    string $label,
    PricingPeriodType $type,
    int $price,
  ): void {
    PricingPeriod::query()->updateOrCreate(
      [
        'book_format_id' => $formatId,
        'label' => $label,
      ],
      [
        'type' => $type,
        'price' => $price,
        'currency' => 'CDF',
        'start_at' => now()->subMonth(),
        'end_at' => now()->addYear(),
        'is_active' => true,
      ],
    );
  }
}
