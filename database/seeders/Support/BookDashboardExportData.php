<?php

namespace Database\Seeders\Support;

/**
 * Données consolidées par livre pour import manuel dans le dashboard Filament.
 */
class BookDashboardExportData
{
  /**
   * Retourne la fiche complète de tous les livres à exporter.
   *
   * @return array<string, array<string, mixed>> Indexé par slug
   */
  public static function books(): array
  {
    return [
      'eglise-face-aux-defis-de-lheure' => self::bookEgliseDefis(),
      'le-poids-du-silence' => self::bookPoidsDuSilence(),
      'generation-debout' => self::bookGenerationDebout(),
      'eglise-face-a-lesprit-de-babylone' => self::bookEspritBabylone(),
    ];
  }

  /**
   * Retourne une fiche livre par slug.
   *
   * @param string $slug Identifiant URL du livre
   * @return array<string, mixed>|null Fiche ou null
   */
  public static function forSlug(string $slug): ?array
  {
    return self::books()[$slug] ?? null;
  }

  /**
   * Fiche — L'Église face aux défis de l'heure.
   *
   * @return array<string, mixed>
   */
  private static function bookEgliseDefis(): array
  {
    return self::buildBook(
      slug: 'eglise-face-aux-defis-de-lheure',
      title: 'L\'Église face aux défis de l\'heure',
      subtitle: 'Discerner les temps, tenir la foi et préparer une génération à se lever.',
      tagline: 'L\'ouvrage phare du Pasteur Ken Luamba',
      category: 'Théologie pratique · Ecclésiologie',
      pageCount: 248,
      readingTimeMinutes: 360,
      releaseDate: '2026-09-12',
      themes: ['Discernement', 'Réveil', 'Génération', 'Mission'],
      accentColor: '#1b1f2a',
      isFeatured: true,
      sortOrder: 1,
      description: 'À une époque traversée par le doute, la confusion et l\'usure spirituelle, l\'Église est appelée à retrouver sa vocation prophétique. Ken Luamba dresse un diagnostic lucide des défis de l\'heure et trace un chemin de fermeté, de profondeur et d\'espérance.',
      authorNote: '« L\'Église face aux défis de l\'heure » n\'est pas un livre de circonstance. C\'est un appel à la maturité pour une génération qui refuse de plier.',
      aboutParagraphs: [
        '« L\'Église face aux défis de l\'heure » n\'est pas un livre de circonstance. C\'est un appel à la maturité. Page après page, l\'auteur invite le lecteur à sortir du christianisme de surface pour redécouvrir une foi enracinée, capable de tenir lorsque les fondements sont ébranlés.',
        'Nourri d\'années d\'enseignement et de ministère au Centre Missionnaire Philadelphie, ce livre articule rigueur théologique et application concrète. Il s\'adresse autant aux responsables qu\'aux croyants en quête de sens.',
        'Chaque chapitre se termine par une méditation et des questions de réflexion, faisant de cet ouvrage un compagnon de croissance, seul ou en groupe.',
      ],
      coverSourceFile: 'Cover Livre - Eglise Defis Heure.jpg',
      backCoverSourceFile: 'Back Cover - Eglise Defis Heure.jpg',
      excerptSlug: 'eglise-face-aux-defis-de-lheure',
      preorderGoal: 1500,
      preorderReserved: 1043,
      preorderBonuses: [
        'Livraison prioritaire garantie',
        'Accès à une conférence exclusive',
        'Dédicace personnalisée de l\'auteur',
      ],
    );
  }

  /**
   * Fiche — Le Poids du Silence.
   *
   * @return array<string, mixed>
   */
  private static function bookPoidsDuSilence(): array
  {
    return self::buildBook(
      slug: 'le-poids-du-silence',
      title: 'Le Poids du Silence',
      subtitle: 'Retrouver la voix de Dieu dans un monde saturé de bruit et d\'urgence.',
      tagline: 'Méditations sur la présence',
      category: 'Vie spirituelle · Intériorité',
      pageCount: 196,
      readingTimeMinutes: 270,
      releaseDate: '2024-03-04',
      themes: ['Silence', 'Prière', 'Présence', 'Repos'],
      accentColor: '#0f172a',
      isFeatured: false,
      sortOrder: 2,
      description: 'Nous avons appris à tout remplir : nos agendas, nos oreilles, nos prières. Ken Luamba explore le silence non comme un vide, mais comme le lieu où la voix de Dieu redevient audible.',
      authorNote: 'Une invitation à ralentir et à redécouvrir le repos, l\'attente et l\'écoute.',
      aboutParagraphs: [
        '« Le Poids du Silence » est une invitation à ralentir. Loin d\'une spiritualité de la performance, l\'auteur propose une redécouverte du repos, de l\'attente et de l\'écoute.',
        'Écrit dans une langue sobre et lumineuse, ce livre se lit comme une retraite. Chaque chapitre ouvre un espace de méditation.',
      ],
      coverSourceFile: 'Cover Livre - Prix du Sacrifice.jpg',
      backCoverSourceFile: 'Backer Cover - Prix du Sacrifice.jpg',
      excerptSlug: 'le-poids-du-silence',
    );
  }

  /**
   * Fiche — Génération Debout.
   *
   * @return array<string, mixed>
   */
  private static function bookGenerationDebout(): array
  {
    return self::buildBook(
      slug: 'generation-debout',
      title: 'Génération Debout',
      subtitle: 'Un manifeste pour une jeunesse de conviction, de courage et d\'espérance.',
      tagline: 'Bientôt disponible',
      category: 'Leadership · Génération',
      pageCount: 224,
      readingTimeMinutes: 300,
      releaseDate: '2026-12-01',
      themes: ['Jeunesse', 'Vision', 'Courage', 'Vocation'],
      accentColor: '#1d2433',
      isFeatured: false,
      sortOrder: 3,
      description: 'À ceux qu\'on dit perdus, distraits, fragiles, Ken Luamba répond par un manifeste. « Génération Debout » trace le portrait d\'une jeunesse appelée non à suivre l\'époque, mais à la marquer.',
      authorNote: 'Un appel au courage et à l\'engagement concret pour une génération debout.',
      aboutParagraphs: [
        '« Génération Debout » est un appel au courage. L\'auteur y déploie une vision exigeante de la vocation, refusant aussi bien le cynisme que l\'illusion.',
        'Conçu comme un parcours, ce livre alterne récits, enseignements et défis concrets pour passer de l\'intention à l\'engagement.',
      ],
      coverSourceFile: 'Cover Livre - Zones Sombres.jpg',
      backCoverSourceFile: 'Back bCover- Zones Sombres.jpg',
      excerptSlug: 'generation-debout',
    );
  }

  /**
   * Fiche — L'Église face à l'esprit de Babylone.
   *
   * @return array<string, mixed>
   */
  private static function bookEspritBabylone(): array
  {
    return self::buildBook(
      slug: 'eglise-face-a-lesprit-de-babylone',
      title: 'L\'Église face à l\'esprit de Babylone',
      subtitle: 'Un parcours prophétique pour discerner sa logique, résister à ses séductions et demeurer fidèle à la Parole.',
      tagline: 'Discerner l\'esprit de Babylone',
      category: 'Discernement spirituel · Prophétique',
      pageCount: 232,
      readingTimeMinutes: 330,
      releaseDate: '2026-09-12',
      themes: ['Babylone', 'Discernement', 'Résistance', 'Fidélité'],
      accentColor: '#2f1a12',
      isFeatured: false,
      sortOrder: 4,
      description: 'Ce livre naît d\'une urgence prophétique : l\'esprit de Babylone n\'est pas une réalité lointaine, mais une logique active qui séduit, normalise et s\'infiltre dans les pensées, les désirs et les choix du croyant.',
      authorNote: 'Un appel nécessaire au discernement et à la consécration face aux séductions de notre temps.',
      aboutParagraphs: [
        'À partir d\'Apocalypse 18:4 — « Sortez du milieu d\'elle, mon peuple » — Ken Luamba montre qu\'il est possible d\'appartenir à Dieu tout en laissant des influences extérieures façonner silencieusement sa vie intérieure.',
        'Le parcours conduit le lecteur du réveil prophétique aux glissements intérieurs, puis aux structures de rébellion, avant de s\'arrêter sur Daniel en territoire hostile comme modèle de fidélité sous pression.',
        'L\'appel à sortir n\'est pas une fuite physique du monde, mais une décision d\'appartenance : refuser les logiques qui contestent la seigneurie de Christ.',
      ],
      coverSourceFile: 'Cover Livre - Esprit de Babylone.jpg',
      backCoverSourceFile: 'Back Cover - Esprit de Babylone.jpg',
      excerptSlug: 'eglise-face-a-lesprit-de-babylone',
      preorderGoal: 1200,
      preorderReserved: 428,
      preorderBonuses: [
        'Livraison prioritaire garantie',
        'Session de questions-réponses en ligne',
      ],
    );
  }

  /**
   * Assemble une fiche livre prête pour l'export dashboard.
   *
   * @param string $slug Slug URL
   * @param string $title Titre
   * @param string $subtitle Sous-titre
   * @param string $tagline Accroche
   * @param string $category Catégorie
   * @param int $pageCount Nombre de pages
   * @param int $readingTimeMinutes Durée lecture en minutes
   * @param string $releaseDate Date de sortie ISO
   * @param list<string> $themes Thèmes
   * @param string $accentColor Couleur accent hex
   * @param bool $isFeatured Livre vedette
   * @param int $sortOrder Ordre catalogue
   * @param string $description Description commerciale
   * @param string $authorNote Mot de l'auteur
   * @param list<string> $aboutParagraphs Paragraphes « À propos »
   * @param string $coverSourceFile Fichier couverture source (book-site)
   * @param string $backCoverSourceFile Fichier verso source (book-site)
   * @param string $excerptSlug Slug pour BookSiteExcerptData
   * @param int|null $preorderGoal Objectif précommande
   * @param int|null $preorderReserved Précommandes enregistrées
   * @param list<string>|null $preorderBonuses Bonus campagne
   * @return array<string, mixed> Fiche structurée
   */
  private static function buildBook(
    string $slug,
    string $title,
    string $subtitle,
    string $tagline,
    string $category,
    int $pageCount,
    int $readingTimeMinutes,
    string $releaseDate,
    array $themes,
    string $accentColor,
    bool $isFeatured,
    int $sortOrder,
    string $description,
    string $authorNote,
    array $aboutParagraphs,
    string $coverSourceFile,
    string $backCoverSourceFile,
    string $excerptSlug,
    ?int $preorderGoal = null,
    ?int $preorderReserved = null,
    ?array $preorderBonuses = null,
  ): array {
    $excerptPages = BookSiteExcerptData::forSlug($excerptSlug);
    $numberedPages = [];

    foreach ($excerptPages as $index => $page) {
      $numberedPages[] = [
        'pageNumber' => $index + 1,
        ...$page,
        'paragraphsText' => isset($page['paragraphs']) && is_array($page['paragraphs'])
          ? implode("\n\n", $page['paragraphs'])
          : null,
      ];
    }

    return [
      'slug' => $slug,
      'importGuide' => [
        'etape1' => 'Admin → Livres → Créer / Modifier le livre',
        'etape2' => 'Section Contenu : copier title, description, author_note, about_paragraphs, themes',
        'etape3' => 'Uploader cover_image et back_cover_image depuis le dossier book-site/public/images',
        'etape4' => 'Section Aperçu feuilletable : recréer chaque page du tableau excerptPages',
        'etape5' => 'Option test PDF : uploader previewPdfPath depuis storage/app/public/books/previews/',
        'imagesSourceDirectory' => 'ken-luamba-book-site/public/images',
      ],
      'sectionIdentification' => [
        'title' => $title,
        'slug' => $slug,
        'subtitle' => $subtitle,
        'tagline' => $tagline,
        'is_featured' => $isFeatured,
        'sort_order' => $sortOrder,
        'is_published' => true,
      ],
      'sectionContenu' => [
        'description' => $description,
        'author_note' => $authorNote,
        'about_paragraphs' => $aboutParagraphs,
        'about_paragraphs_text' => implode("\n\n", $aboutParagraphs),
        'themes' => $themes,
        'themes_text' => implode("\n", $themes),
      ],
      'sectionFicheEditoriale' => [
        'page_count' => $pageCount,
        'reading_time_minutes' => $readingTimeMinutes,
        'language' => 'Français',
        'release_date' => $releaseDate,
        'accent_color' => $accentColor,
        'category' => $category,
        'preorder_campaign_goal' => $preorderGoal,
        'preorder_reserved_count' => $preorderReserved ?? 0,
        'preorder_campaign_bonuses' => $preorderBonuses ?? [],
        'preorder_campaign_bonuses_text' => $preorderBonuses !== null
          ? implode("\n", $preorderBonuses)
          : '',
      ],
      'sectionVisuels' => [
        'cover_image_source' => $coverSourceFile,
        'back_cover_image_source' => $backCoverSourceFile,
        'preview_pdf_destination' => 'books/previews/'.$slug.'.pdf',
      ],
      'excerptPages' => $numberedPages,
      'excerptPageCount' => count($numberedPages),
    ];
  }
}
