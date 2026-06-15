<?php

namespace Database\Seeders;

use App\Enums\BookFormatType;
use App\Enums\DigitalFileType;
use App\Enums\PricingPeriodType;
use App\Models\Author;
use App\Models\Book;
use App\Models\BookFormat;
use App\Models\PricingPeriod;
use Database\Seeders\Support\BookSiteExcerptData;
use Database\Seeders\Support\SeederDigitalFileService;
use Database\Seeders\Support\SeederMediaService;
use Illuminate\Database\Seeder;

/**
 * Alimente le catalogue avec les ouvrages de la maquette book-site.
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

    $media = new SeederMediaService();
    $imagesDir = $media->bookSiteImagesDirectory();

    $authorImage = $media->publishFile(
      $imagesDir.DIRECTORY_SEPARATOR.'pasteur-ken.png',
      'authors/ken-luamba.png',
    );

    if ($authorImage !== null) {
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

    Book::query()
      ->where('slug', 'mon-premier-ouvrage')
      ->update(['is_published' => false, 'is_featured' => false]);

    $this->seedEgliseBook($author, $media, $imagesDir);
    $this->seedSilenceBook($author, $media, $imagesDir);
    $this->seedGenerationBook($author, $media, $imagesDir);
    $this->seedBabyloneBook($author, $media, $imagesDir);
  }

  /**
   * Publie les visuels recto/verso d'un livre depuis le dossier book-site.
   *
   * @param SeederMediaService $media Service médias
   * @param string $imagesDir Dossier images book-site
   * @param string $slug Slug du livre (nom de fichier destination)
   * @param string $frontFile Nom fichier couverture source
   * @param string $backFile Nom fichier quatrième de couverture source
   * @param string $title Titre pour placeholder
   * @param string $accent Couleur placeholder
   * @return array{cover: string, back: string|null} Chemins relatifs publiés
   */
  private function publishBookCovers(
    SeederMediaService $media,
    string $imagesDir,
    string $slug,
    string $frontFile,
    string $backFile,
    string $title,
    string $accent,
  ): array {
    $coverPath = $media->publishFile(
      $imagesDir.DIRECTORY_SEPARATOR.$frontFile,
      'books/covers/'.$slug.'.jpg',
    ) ?? $media->generateCoverPlaceholder(
      'books/covers/'.$slug.'.jpg',
      $title,
      $accent,
    );

    $backPath = $media->publishFile(
      $imagesDir.DIRECTORY_SEPARATOR.$backFile,
      'books/covers/back/'.$slug.'-back.jpg',
    );

    return ['cover' => $coverPath, 'back' => $backPath];
  }

  /**
   * Crée le livre phare « L'Église face aux défis de l'heure ».
   *
   * @param Author $author Auteur principal
   * @param SeederMediaService $media Service médias
   * @param string $imagesDir Dossier images book-site
   */
  private function seedEgliseBook(Author $author, SeederMediaService $media, string $imagesDir): void
  {
    $covers = $this->publishBookCovers(
      $media,
      $imagesDir,
      'eglise-face-aux-defis-de-lheure',
      'Cover Livre - Eglise Defis Heure.jpg',
      'Back Cover - Eglise Defis Heure.jpg',
      "L'Église face aux défis de l'heure",
      '#1b1f2a',
    );

    $book = Book::query()->updateOrCreate(
      ['slug' => 'eglise-face-aux-defis-de-lheure'],
      [
        'author_id' => $author->id,
        'title' => 'L\'Église face aux défis de l\'heure',
        'subtitle' => 'Discerner les temps, tenir la foi et préparer une génération à se lever.',
        'tagline' => 'L\'ouvrage phare du Pasteur Ken Luamba',
        'category' => 'Théologie pratique · Ecclésiologie',
        'page_count' => 248,
        'reading_time_minutes' => 360,
        'language' => 'Français',
        'release_date' => '2026-09-12',
        'themes' => ['Discernement', 'Réveil', 'Génération', 'Mission'],
        'about_paragraphs' => [
          '« L\'Église face aux défis de l\'heure » n\'est pas un livre de circonstance. C\'est un appel à la maturité. Page après page, l\'auteur invite le lecteur à sortir du christianisme de surface pour redécouvrir une foi enracinée, capable de tenir lorsque les fondements sont ébranlés.',
          'Nourri d\'années d\'enseignement et de ministère au Centre Missionnaire Philadelphie, ce livre articule rigueur théologique et application concrète. Il s\'adresse autant aux responsables qu\'aux croyants en quête de sens.',
          'Chaque chapitre se termine par une méditation et des questions de réflexion, faisant de cet ouvrage un compagnon de croissance, seul ou en groupe.',
        ],
        'excerpt' => BookSiteExcerptData::forSlug('eglise-face-aux-defis-de-lheure'),
        'accent_color' => '#1b1f2a',
        'preorder_campaign_goal' => 1500,
        'preorder_reserved_count' => 1043,
        'preorder_campaign_bonuses' => [
          'Livraison prioritaire garantie',
          'Accès à une conférence exclusive',
          'Dédicace personnalisée de l\'auteur',
        ],
        'description' => 'À une époque traversée par le doute, la confusion et l\'usure spirituelle, l\'Église est appelée à retrouver sa vocation prophétique. Ken Luamba dresse un diagnostic lucide des défis de l\'heure et trace un chemin de fermeté, de profondeur et d\'espérance.',
        'author_note' => '« L\'Église face aux défis de l\'heure » n\'est pas un livre de circonstance. C\'est un appel à la maturité pour une génération qui refuse de plier.',
        'cover_image' => $covers['cover'],
        'back_cover_image' => $covers['back'],
        'is_published' => true,
        'is_featured' => true,
        'sort_order' => 1,
        'published_at' => now()->subMonths(1),
      ],
    );

    $hardcover = $this->upsertFormat($book, 'KL-EG-HC', BookFormatType::Hardcover, 120, true);
    $ebook = $this->upsertFormat($book, 'KL-EG-EB', BookFormatType::Ebook, null, true, DigitalFileType::Epub);
    $audio = $this->upsertFormat($book, 'KL-EG-AU', BookFormatType::Audiobook, null, true, DigitalFileType::Mp3);

    $digitalFiles = new SeederDigitalFileService();
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
   * Crée le livre « Le Poids du Silence ».
   *
   * @param Author $author Auteur principal
   * @param SeederMediaService $media Service médias
   */
  private function seedSilenceBook(Author $author, SeederMediaService $media, string $imagesDir): void
  {
    $covers = $this->publishBookCovers(
      $media,
      $imagesDir,
      'le-poids-du-silence',
      'Cover Livre - Prix du Sacrifice.jpg',
      'Backer Cover - Prix du Sacrifice.jpg',
      'Le Poids du Silence',
      '#0f172a',
    );

    $book = Book::query()->updateOrCreate(
      ['slug' => 'le-poids-du-silence'],
      [
        'author_id' => $author->id,
        'title' => 'Le Poids du Silence',
        'subtitle' => 'Retrouver la voix de Dieu dans un monde saturé de bruit et d\'urgence.',
        'tagline' => 'Méditations sur la présence',
        'category' => 'Vie spirituelle · Intériorité',
        'page_count' => 196,
        'reading_time_minutes' => 270,
        'language' => 'Français',
        'release_date' => '2024-03-04',
        'themes' => ['Silence', 'Prière', 'Présence', 'Repos'],
        'about_paragraphs' => [
          '« Le Poids du Silence » est une invitation à ralentir. Loin d\'une spiritualité de la performance, l\'auteur propose une redécouverte du repos, de l\'attente et de l\'écoute.',
          'Écrit dans une langue sobre et lumineuse, ce livre se lit comme une retraite. Chaque chapitre ouvre un espace de méditation.',
        ],
        'excerpt' => BookSiteExcerptData::forSlug('le-poids-du-silence'),
        'accent_color' => '#0f172a',
        'description' => 'Nous avons appris à tout remplir : nos agendas, nos oreilles, nos prières. Ken Luamba explore le silence non comme un vide, mais comme le lieu où la voix de Dieu redevient audible.',
        'author_note' => 'Une invitation à ralentir et à redécouvrir le repos, l\'attente et l\'écoute.',
        'cover_image' => $covers['cover'],
        'back_cover_image' => $covers['back'],
        'is_published' => true,
        'is_featured' => false,
        'sort_order' => 2,
        'published_at' => now()->subYears(2),
      ],
    );

    $paperback = $this->upsertFormat($book, 'KL-PS-PB', BookFormatType::Paperback, 80, true);
    $ebook = $this->upsertFormat($book, 'KL-PS-EB', BookFormatType::Ebook, null, true, DigitalFileType::Epub);
    $audio = $this->upsertFormat($book, 'KL-PS-AU', BookFormatType::Audiobook, null, true, DigitalFileType::Mp3);

    $this->upsertPricing($paperback->id, 'Broché', PricingPeriodType::Regular, 25000);
    $this->upsertPricing($ebook->id, 'Ebook', PricingPeriodType::Regular, 11000);
    $this->upsertPricing($audio->id, 'Audio', PricingPeriodType::Regular, 15000);
  }

  /**
   * Crée le livre « Génération Debout » (à paraître).
   *
   * @param Author $author Auteur principal
   * @param SeederMediaService $media Service médias
   */
  private function seedGenerationBook(Author $author, SeederMediaService $media, string $imagesDir): void
  {
    $covers = $this->publishBookCovers(
      $media,
      $imagesDir,
      'generation-debout',
      'Cover Livre - Zones Sombres.jpg',
      'Back bCover- Zones Sombres.jpg',
      'Génération Debout',
      '#1d2433',
    );

    $book = Book::query()->updateOrCreate(
      ['slug' => 'generation-debout'],
      [
        'author_id' => $author->id,
        'title' => 'Génération Debout',
        'subtitle' => 'Un manifeste pour une jeunesse de conviction, de courage et d\'espérance.',
        'tagline' => 'Bientôt disponible',
        'category' => 'Leadership · Génération',
        'page_count' => 224,
        'reading_time_minutes' => 300,
        'language' => 'Français',
        'release_date' => '2026-12-01',
        'themes' => ['Jeunesse', 'Vision', 'Courage', 'Vocation'],
        'about_paragraphs' => [
          '« Génération Debout » est un appel au courage. L\'auteur y déploie une vision exigeante de la vocation, refusant aussi bien le cynisme que l\'illusion.',
          'Conçu comme un parcours, ce livre alterne récits, enseignements et défis concrets pour passer de l\'intention à l\'engagement.',
        ],
        'excerpt' => BookSiteExcerptData::forSlug('generation-debout'),
        'accent_color' => '#1d2433',
        'description' => 'À ceux qu\'on dit perdus, distraits, fragiles, Ken Luamba répond par un manifeste. « Génération Debout » trace le portrait d\'une jeunesse appelée non à suivre l\'époque, mais à la marquer.',
        'author_note' => 'Un appel au courage et à l\'engagement concret pour une génération debout.',
        'cover_image' => $covers['cover'],
        'back_cover_image' => $covers['back'],
        'is_published' => true,
        'is_featured' => false,
        'sort_order' => 3,
        'published_at' => now()->addMonths(6),
      ],
    );

    $this->upsertFormat($book, 'KL-GD-HC', BookFormatType::Hardcover, 0, false);
    $this->upsertFormat($book, 'KL-GD-EB', BookFormatType::Ebook, null, false, DigitalFileType::Epub);
    $this->upsertFormat($book, 'KL-GD-AU', BookFormatType::Audiobook, null, false, DigitalFileType::Mp3);
  }

  /**
   * Crée le livre « L'Église face à l'esprit de Babylone » (précommande).
   *
   * @param Author $author Auteur principal
   * @param SeederMediaService $media Service médias
   * @param string $imagesDir Dossier images book-site
   */
  private function seedBabyloneBook(Author $author, SeederMediaService $media, string $imagesDir): void
  {
    $covers = $this->publishBookCovers(
      $media,
      $imagesDir,
      'eglise-face-a-lesprit-de-babylone',
      'Cover Livre - Esprit de Babylone.jpg',
      'Back Cover - Esprit de Babylone.jpg',
      "L'Église face à l'esprit de Babylone",
      '#2f1a12',
    );

    $book = Book::query()->updateOrCreate(
      ['slug' => 'eglise-face-a-lesprit-de-babylone'],
      [
        'author_id' => $author->id,
        'title' => 'L\'Église face à l\'esprit de Babylone',
        'subtitle' => 'Un parcours prophétique pour discerner sa logique, résister à ses séductions et demeurer fidèle à la Parole.',
        'tagline' => 'Discerner l\'esprit de Babylone',
        'category' => 'Discernement spirituel · Prophétique',
        'page_count' => 232,
        'reading_time_minutes' => 330,
        'language' => 'Français',
        'release_date' => '2026-09-12',
        'themes' => ['Babylone', 'Discernement', 'Résistance', 'Fidélité'],
        'about_paragraphs' => [
          'À partir d\'Apocalypse 18:4 — « Sortez du milieu d\'elle, mon peuple » — Ken Luamba montre qu\'il est possible d\'appartenir à Dieu tout en laissant des influences extérieures façonner silencieusement sa vie intérieure.',
          'Le parcours conduit le lecteur du réveil prophétique aux glissements intérieurs, puis aux structures de rébellion, avant de s\'arrêter sur Daniel en territoire hostile comme modèle de fidélité sous pression.',
          'L\'appel à sortir n\'est pas une fuite physique du monde, mais une décision d\'appartenance : refuser les logiques qui contestent la seigneurie de Christ.',
        ],
        'excerpt' => BookSiteExcerptData::forSlug('eglise-face-a-lesprit-de-babylone'),
        'accent_color' => '#2f1a12',
        'preorder_campaign_goal' => 1200,
        'preorder_reserved_count' => 428,
        'preorder_campaign_bonuses' => [
          'Livraison prioritaire garantie',
          'Session de questions-réponses en ligne',
        ],
        'description' => 'Ce livre naît d\'une urgence prophétique : l\'esprit de Babylone n\'est pas une réalité lointaine, mais une logique active qui séduit, normalise et s\'infiltre dans les pensées, les désirs et les choix du croyant.',
        'author_note' => 'Un appel nécessaire au discernement et à la consécration face aux séductions de notre temps.',
        'cover_image' => $covers['cover'],
        'back_cover_image' => $covers['back'],
        'is_published' => true,
        'is_featured' => false,
        'sort_order' => 4,
        'published_at' => now()->subWeeks(2),
      ],
    );

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
