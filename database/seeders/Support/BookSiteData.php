<?php

namespace Database\Seeders\Support;

/**
 * Données catalogue alignées sur ken-luamba-book-site/src/data/books.ts (ordre et contenu).
 */
class BookSiteData
{
  /**
   * Date de sortie précommande du book-site.
   */
  public const PREORDER_RELEASE_DATE = '2026-06-28';

  /**
   * Retourne les livres dans l'ordre exact du book-site.
   *
   * @return list<array<string, mixed>>
   */
  public static function books(): array
  {
    return [
      self::egliseDefis(),
      self::prixDuSacrifice(),
      self::zonesSombres(),
      self::espritDeBabylone(),
    ];
  }

  /**
   * Retourne un livre par slug.
   *
   * @param string $slug Identifiant URL
   * @return array<string, mixed>|null
   */
  public static function forSlug(string $slug): ?array
  {
    foreach (self::books() as $book) {
      if (($book['slug'] ?? '') === $slug) {
        return $book;
      }
    }

    return null;
  }

  /**
   * Décode un chemin image book-site (/images/...) vers le nom de fichier.
   *
   * @param string|null $path Chemin URL book-site
   * @return string|null Nom de fichier local
   */
  public static function imageFileName(?string $path): ?string
  {
    if ($path === null || $path === '') {
      return null;
    }

    $decoded = urldecode($path);
    $basename = basename($decoded);

    return $basename !== '' ? $basename : null;
  }

  /**
   * Livre 1 — L'Église face aux défis de l'heure.
   *
   * @return array<string, mixed>
   */
  private static function egliseDefis(): array
  {
    return [
      'order' => 1,
      'slug' => 'eglise-face-aux-defis-de-lheure',
      'title' => 'L\'Église face aux défis de l\'heure',
      'subtitle' => 'Un appel prophétique au réveil, à la sainteté et à la fidélité jusqu\'au retour du Roi.',
      'status' => 'preorder',
      'accent' => '#1b1f2a',
      'cover' => '/images/Cover%20Livre%20-%20Eglise%20Defis%20Heure.jpg',
      'backCover' => '/images/Back%20Cover%20-%20Eglise%20Defis%20Heure.jpg',
      'releaseDate' => self::PREORDER_RELEASE_DATE,
      'pages' => 248,
      'readingTime' => '6 h de lecture',
      'language' => 'Français',
      'category' => 'Ecclésiologie · Réveil spirituel',
      'themes' => ['Réveil', 'Sainteté', 'Apostasie', 'Veilleurs'],
      'tagline' => 'L\'ouvrage phare du Pasteur Ken Luamba',
      'summary' => 'Né d\'un fardeau porté dans la prière, ce livre sonne comme une trompette pour l\'Église de Jésus-Christ. Le Pasteur Ken Luamba y dresse un diagnostic lucide d\'une foi qui se refroidit, d\'une piété devenue apparente et d\'un message de la croix parfois remplacé par un évangile de confort.',
      'about' => [
        '« L\'Église face aux défis de l\'heure » est écrit en sentinelle : non pour condamner, mais pour avertir ; non pour blesser, mais pour réveiller. L\'auteur appelle à la repentance sincère, à la radicalité spirituelle et à la consécration.',
        'Les premiers chapitres dressent l\'état d\'une Église confrontée à la perte du discernement, aux compromis du monde, aux fausses doctrines, à l\'infiltration de l\'esprit de Babylone et à l\'apostasie rampante.',
        'Le livre trace ensuite le chemin de la restauration : revenir à l\'autel de Dieu, former des veilleurs, discerner les temps et demeurer fidèle jusqu\'au retour du Seigneur.',
      ],
      'formats' => [
        ['kind' => 'paper', 'label' => 'Édition imprimée', 'price' => 24, 'available' => true, 'detail' => 'Précommande · Couverture souple'],
        ['kind' => 'epub', 'label' => 'Édition numérique (EPUB)', 'price' => 14, 'available' => true, 'detail' => 'Précommande · Compatible liseuses & mobile'],
        ['kind' => 'audio', 'label' => 'Livre audio', 'price' => 18, 'available' => true, 'detail' => 'Précommande · Lu par l\'auteur'],
      ],
      'campaign' => [
        'goal' => 1500,
        'reserved' => 1043,
        'bonus' => [
          'Chapitre inédit en avant-première',
          'Accès au webinaire de lancement',
          'Nom inscrit dans les remerciements de l\'édition limitée',
        ],
      ],
      'reviews' => [
        ['name' => 'Esther M.', 'role' => 'Responsable de cellule', 'rating' => 5, 'quote' => 'Un livre qui secoue avec douceur et reconstruit avec profondeur. Je l\'ai lu deux fois.'],
        ['name' => 'Daniel K.', 'role' => 'Étudiant en théologie', 'rating' => 5, 'quote' => 'La clarté de l\'analyse m\'a marqué. Chaque chapitre se médite autant qu\'il se lit.'],
        ['name' => 'Grâce N.', 'role' => 'Lectrice', 'rating' => 5, 'quote' => 'Enfin un ouvrage qui parle vrai à notre génération sans jamais la flatter.'],
      ],
      'excerpt' => [
        ['kind' => 'cover'],
        ['kind' => 'section', 'eyebrow' => 'I. Présentation et contexte du livre', 'title' => 'Un fardeau porté dans la prière', 'paragraphs' => [
          'Ce livre est né d\'un fardeau. Pas d\'une réflexion théologique de cabinet, pas d\'une commande intellectuelle — mais d\'un poids porté dans la prière, d\'un cri intérieur pour l\'état de l\'Église de Jésus-Christ.',
          'Le Pasteur Ken Luamba écrit en sentinelle placée sur la muraille : non pour condamner, mais pour avertir ; non pour blesser, mais pour réveiller.',
        ]],
        ['kind' => 'section', 'eyebrow' => 'Présentation et contexte', 'title' => 'Le diagnostic de départ', 'paragraphs' => [
          'Le constat de départ est sévère mais juste : la foi se refroidit. La piété est devenue apparente plutôt que réelle. Le message exigeant de la croix laisse parfois place à un évangile centré sur le bien-être personnel.',
          'La sanctification est perçue comme une option et non comme une exigence. La crainte de Dieu s\'efface tandis que l\'immoralité s\'installe comme une norme acceptable.',
          'Face à ce diagnostic, l\'auteur ne se résigne pas. Il lance un appel solennel à la repentance sincère, à la radicalité spirituelle et à la consécration.',
        ]],
        ['kind' => 'part', 'eyebrow' => 'Première partie', 'title' => 'Le diagnostic : une Église en crise', 'paragraphs' => [
          'Les sept premiers chapitres dressent un état des lieux lucide de l\'Église contemporaine. Le premier défi identifié est la perte du discernement spirituel : il est possible d\'être actif sans être lucide, religieux sans être perspicace, d\'avancer sans direction.',
          'L\'auteur examine ensuite l\'Église tiraillée entre deux autels — celui de Dieu et celui du confort du monde — avant d\'analyser l\'oubli progressif de la mission évangélique, le paradoxe d\'une Église hyperconnectée mais spirituellement isolée, la séduction des fausses doctrines, l\'infiltration de l\'esprit de Babylone dans la génération actuelle, et enfin l\'apostasie rampante qui gagne du terrain dans le silence.',
        ]],
        ['kind' => 'part', 'eyebrow' => 'Deuxième partie', 'title' => 'La réponse : restaurer, veiller, demeurer', 'paragraphs' => [
          'L\'auteur ne s\'arrête pas au diagnostic. Il trace le chemin de la restauration.',
          'Le chapitre 8 appelle à la restauration de l\'autel de Dieu — revenir à la prière, à la consécration, à la vérité, à la croix.',
          'Le chapitre 9 est un appel aux veilleurs : ces hommes et ces femmes capables d\'entendre la voix de Dieu, de discerner les temps et de tenir leur position malgré l\'obscurité.',
        ]],
        ['kind' => 'part', 'eyebrow' => 'Troisième partie', 'title' => 'La vision : l\'Église glorieuse', 'paragraphs' => [
          'Malgré les compromis et malgré les ténèbres, l\'auteur clôt son livre sur une espérance intacte. L\'Église de Jésus-Christ ne sera jamais vaincue.',
          'Dieu prépare encore une Église : enracinée dans la Parole, conduite par l\'Esprit, fidèle jusqu\'à la fin, sanctifiée pour le retour du Seigneur.',
          'Une Église sans tache ni ride. Une Église qui préfère la vérité au compromis. Une Église qui porte encore le feu de Dieu.',
        ]],
        ['kind' => 'backCover'],
      ],
      'featured' => true,
    ];
  }

  /**
   * Livre 2 — Le Prix du Sacrifice (slug le-poids-du-silence).
   *
   * @return array<string, mixed>
   */
  private static function prixDuSacrifice(): array
  {
    return [
      'order' => 2,
      'slug' => 'le-poids-du-silence',
      'title' => 'Le Prix du Sacrifice',
      'subtitle' => 'Retrouver la place du renoncement, de la croix et de l\'autel dans la vie chrétienne.',
      'status' => 'preorder',
      'accent' => '#0f172a',
      'cover' => '/images/Cover%20Livre%20-%20Prix%20du%20Sacrifice.jpg',
      'backCover' => '/images/Backer%20Cover%20-%20Prix%20du%20Sacrifice.jpg',
      'releaseDate' => self::PREORDER_RELEASE_DATE,
      'pages' => 196,
      'readingTime' => '4 h 30 de lecture',
      'language' => 'Français',
      'category' => 'Discipolat · Vie chrétienne',
      'themes' => ['Renoncement', 'Croix', 'Consécration', 'Autel'],
      'tagline' => 'Retrouver le chemin de l\'autel',
      'summary' => 'Dans « Le Prix du Sacrifice », Ken Luamba restaure l\'intelligence spirituelle de l\'autel. Il rappelle qu\'avant la terre promise, le palais, le trône ou la résurrection, il y a toujours un lieu de renoncement où Dieu détache, vide, façonne et prépare.',
      'about' => [
        'Ce livre naît d\'un constat douloureux : une génération aime les promesses mais fuit parfois les exigences, désire la gloire mais refuse la croix, veut aller loin avec Dieu sans rien perdre.',
        'L\'auteur présente le sacrifice non comme une punition ni comme une souffrance recherchée pour elle-même, mais comme une loi spirituelle : l\'autel précède toujours la gloire.',
        'Structuré autour du renoncement, de la croix, de ce que produit le sacrifice et de la disparition de la croix dans l\'évangile contemporain, l\'ouvrage conduit le lecteur vers une décision personnelle concrète.',
      ],
      'formats' => [
        ['kind' => 'paper', 'label' => 'Édition imprimée', 'price' => 19, 'available' => true, 'detail' => 'Précommande · Couverture souple'],
        ['kind' => 'epub', 'label' => 'Édition numérique (EPUB)', 'price' => 11, 'available' => true, 'detail' => 'Précommande · Compatible liseuses & mobile'],
        ['kind' => 'audio', 'label' => 'Livre audio', 'price' => 15, 'available' => true, 'detail' => 'Précommande · Lu par l\'auteur'],
      ],
      'reviews' => [
        ['name' => 'Joëlle T.', 'role' => 'Lectrice', 'rating' => 5, 'quote' => 'Une respiration. J\'ai redécouvert la prière silencieuse.'],
        ['name' => 'Samuel B.', 'role' => 'Pasteur', 'rating' => 4, 'quote' => 'Un texte délicat et exigeant à la fois.'],
      ],
      'excerpt' => [
        ['kind' => 'cover'],
        ['kind' => 'section', 'eyebrow' => 'I. Présentation et contexte du livre', 'title' => 'Retrouver le chemin de l\'autel', 'paragraphs' => [
          '« Le Prix du Sacrifice » naît d\'un constat douloureux : quelque chose d\'essentiel a progressivement disparu de beaucoup de vies chrétiennes. L\'autel.',
          'Nous vivons dans une génération qui aime les promesses mais fuit les exigences. Une génération qui désire la gloire mais refuse la croix. Plusieurs veulent aller loin avec Dieu — sans rien perdre.',
        ]],
        ['kind' => 'section', 'eyebrow' => 'Présentation et contexte', 'title' => 'L\'autel précède toujours la gloire', 'paragraphs' => [
          'Le Pasteur Ken Luamba engage dans ce livre un plaidoyer scripturaire rigoureux pour restaurer l\'intelligence du sacrifice dans la vie chrétienne contemporaine.',
          'Il part d\'une observation fondamentale : dans les Écritures, chaque fois que Dieu veut conduire un homme vers sa destinée, un autel apparaît.',
          'Avant la terre promise à Abraham, il y a le départ. Avant le palais de Joseph, il y a la fosse. Avant le trône de David, il y a le désert. Avant la gloire de la résurrection, il y a la croix.',
        ]],
        ['kind' => 'part', 'eyebrow' => 'Première partie', 'title' => 'L\'appel au sacrifice — Porter sa croix chaque jour', 'paragraphs' => [
          'L\'auteur commence par établir que Jésus présente le sacrifice non comme une option spirituelle avancée réservée à une élite, mais comme la condition normale du discipolat.',
          'Trois réalités inséparables composent la réponse du disciple — le renoncement à soi-même, le port de la croix, et la marche à la suite de Christ.',
        ]],
        ['kind' => 'part', 'eyebrow' => 'Deuxième partie', 'title' => 'La croix — Une réalité à comprendre et à vivre', 'paragraphs' => [
          'La croix n\'est pas un événement historique passé à célébrer une fois l\'an ; elle est une réalité à embrasser chaque jour.',
          'L\'auteur distingue soigneusement le sacrifice comme punition, conception erronée, du sacrifice comme loi spirituelle, conception scripturaire.',
        ]],
        ['kind' => 'part', 'eyebrow' => 'Troisième partie', 'title' => 'Ce que produit le sacrifice', 'paragraphs' => [
          'L\'auteur y montre que le sacrifice n\'est jamais stérile dans l\'économie de Dieu.',
          'Il produit : une transformation profonde du caractère, une fécondité spirituelle durable, une autorité que le confort seul ne peut conférer, et une communion avec Dieu qui dépasse toute connaissance doctrinale.',
        ]],
        ['kind' => 'part', 'eyebrow' => 'Quatrième partie', 'title' => 'La disparition de la croix', 'paragraphs' => [
          'Le dernier chapitre analyse avec une lucidité prophétique la dérive contemporaine : un évangile qui a progressivement gardé les promesses mais perdu les autels.',
          'L\'auteur décrit comment le message de la croix a été édulcoré, comment le discipolat a été réduit à l\'adhésion à un programme, et comment une génération entière grandit dans une foi déclarative mais dépourvue de profondeur sacrificielle.',
        ]],
        ['kind' => 'backCover'],
      ],
    ];
  }

  /**
   * Livre 3 — Les Zones Sombres du Cœur Humain (slug generation-debout).
   *
   * @return array<string, mixed>
   */
  private static function zonesSombres(): array
  {
    return [
      'order' => 3,
      'slug' => 'generation-debout',
      'title' => 'Les Zones Sombres du Cœur Humain',
      'subtitle' => 'Tome 1 — Révélation, manifestations et ravages des ténèbres intérieures.',
      'status' => 'preorder',
      'accent' => '#1d2433',
      'cover' => '/images/Cover%20Livre%20-%20Zones%20Sombres.jpg',
      'backCover' => '/images/Back%20bCover-%20Zones%20Sombres.jpg',
      'releaseDate' => self::PREORDER_RELEASE_DATE,
      'pages' => 224,
      'readingTime' => '5 h de lecture',
      'language' => 'Français',
      'category' => 'Psychologie spirituelle · Counseling pastoral',
      'themes' => ['Blessures intérieures', 'Guérison', 'Cœur', 'Restauration'],
      'tagline' => 'Diagnostic spirituel du cœur',
      'summary' => 'Né d\'un enseignement sur Caïn, ce livre explore les régions silencieuses du cœur humain : blessures anciennes, peurs enfouies, frustrations accumulées et mécanismes de défense que l\'homme apprend parfois à dissimuler même à lui-même.',
      'about' => [
        '« Les Zones Sombres du Cœur Humain » se présente comme un diagnostic honnête, respectueux et sans condamnation sur les profondeurs invisibles que beaucoup portent sans les comprendre.',
        'L\'auteur montre comment une humiliation ancienne, un rejet, une trahison ou une peur enfouie peuvent continuer à gouverner les émotions, les relations et même la vie spirituelle.',
        'Mais le diagnostic ouvre sur un chemin de restauration : Dieu sonde le cœur non pour condamner, mais pour faire entrer Sa lumière dans les régions blessées et y commencer la guérison.',
      ],
      'formats' => [
        ['kind' => 'paper', 'label' => 'Édition imprimée', 'price' => 22, 'available' => true, 'detail' => 'Précommande · Couverture souple'],
        ['kind' => 'epub', 'label' => 'Édition numérique (EPUB)', 'price' => 13, 'available' => true, 'detail' => 'Précommande · Compatible liseuses & mobile'],
        ['kind' => 'audio', 'label' => 'Livre audio', 'price' => 17, 'available' => true, 'detail' => 'Précommande · Lu par l\'auteur'],
      ],
      'reviews' => [
        ['name' => 'Comité de lecture', 'role' => 'Éditions Philadelphie', 'rating' => 5, 'quote' => 'Le texte le plus mobilisateur de l\'auteur à ce jour.'],
      ],
      'excerpt' => [
        ['kind' => 'cover'],
        ['kind' => 'section', 'eyebrow' => 'I. Présentation et contexte du livre', 'title' => 'Caïn comme miroir du cœur', 'paragraphs' => [
          '« Les Zones Sombres du Cœur Humain » est né d\'un enseignement sur Caïn — cet homme qui, par jalousie et haine silencieuse, finit par ôter la vie à son propre frère.',
          'Mais très vite, l\'auteur a compris que Caïn n\'est pas une exception : il est un miroir. C\'est pourquoi ce qui devait être un simple enseignement est devenu un livre.',
        ]],
        ['kind' => 'section', 'eyebrow' => 'Présentation et contexte', 'title' => 'Un diagnostic des profondeurs invisibles', 'paragraphs' => [
          'Le Pasteur Ken Luamba part d\'un constat douloureux mais lucide : il existe dans le cœur humain des régions silencieuses que peu de personnes osent réellement explorer.',
          'Des profondeurs invisibles où se cachent des blessures anciennes, des peurs enfouies, des frustrations accumulées, des mécanismes de défense que l\'homme a appris à dissimuler même à lui-même.',
          'Ce livre se présente avant tout comme un diagnostic — honnête, respectueux, sans condamnation — sur ces régions intérieures que beaucoup portent sans les comprendre.',
        ]],
        ['kind' => 'part', 'eyebrow' => 'Première partie', 'title' => 'Le cœur, territoire caché de l\'homme', 'paragraphs' => [
          'L\'auteur établit dès le départ la thèse centrale : Dieu regarde au cœur, pas à l\'apparence.',
          'Le cœur humain n\'est pas qu\'un centre émotionnel ; il est le véritable poste de commandement de toute la vie intérieure.',
          'L\'homme peut aimer Dieu sincèrement tout en portant des régions encore fermées à Sa lumière.',
        ]],
        ['kind' => 'part', 'eyebrow' => 'Deuxième partie', 'title' => 'La naissance des ténèbres intérieures', 'paragraphs' => [
          'Les blessures invisibles de l\'âme constituent le cœur de cette partie. L\'auteur montre que le cœur humain possède une mémoire profonde.',
          'Une humiliation vécue dans l\'enfance peut continuer à gouverner un adulte extérieurement mature. Un rejet ancien produit une peur permanente de ne jamais être aimé.',
        ]],
        ['kind' => 'part', 'eyebrow' => 'Troisième partie', 'title' => 'Les manifestations des zones sombres', 'paragraphs' => [
          'Les zones sombres ne restent jamais passives. Elles se manifestent à travers des troubles intérieurs : colères disproportionnées, angoisses persistantes, méfiance chronique, besoin excessif de contrôle, incapacité à recevoir l\'amour.',
          'Ces réactions ne sont souvent pas des défauts de caractère, mais les symptômes visibles de blessures invisibles jamais traitées.',
        ]],
        ['kind' => 'part', 'eyebrow' => 'Quatrième partie', 'title' => 'Les ravages des zones sombres', 'paragraphs' => [
          'Cette partie mesure l\'impact des zones sombres sur la vie intérieure, les relations et la vie spirituelle.',
          'Dans les relations, les blessures cachées blessent ceux qu\'on voulait aimer. Dans la vie spirituelle, certaines personnes aiment sincèrement Dieu mais restent incapables de croire profondément qu\'elles sont aimées par Lui.',
        ]],
        ['kind' => 'part', 'eyebrow' => 'Cinquième partie', 'title' => 'La lumière qui expose les ténèbres', 'paragraphs' => [
          'Dieu commence à sonder le cœur. La confrontation avec soi-même devient inévitable.',
          'L\'auteur décrit le brisement intérieur non comme une destruction mais comme le début de la restauration : lorsque l\'homme cesse enfin de fuir certaines vérités sur lui-même.',
        ]],
        ['kind' => 'part', 'eyebrow' => 'Sixième partie', 'title' => 'Le chemin de la restauration', 'paragraphs' => [
          'La guérison commence dans la lumière. L\'auteur conclut sur une promesse scripturaire : « Il guérit ceux qui ont le cœur brisé, et il panse leurs blessures » (Psaume 147:3).',
          'Dieu ne travaille jamais superficiellement. Il descend dans les profondeurs blessées — non pour condamner, mais pour restaurer.',
        ]],
        ['kind' => 'backCover'],
      ],
    ];
  }

  /**
   * Livre 4 — L'Église face à l'esprit de Babylone.
   *
   * @return array<string, mixed>
   */
  private static function espritDeBabylone(): array
  {
    return [
      'order' => 4,
      'slug' => 'eglise-face-a-lesprit-de-babylone',
      'title' => 'L\'Église face à l\'esprit de Babylone',
      'subtitle' => 'Un parcours prophétique pour discerner sa logique, résister à ses séductions et demeurer fidèle à la Parole.',
      'status' => 'preorder',
      'accent' => '#2f1a12',
      'cover' => '/images/Cover%20Livre%20-%20Esprit%20de%20Babylone.jpg',
      'backCover' => '/images/Back%20Cover%20-%20Esprit%20de%20Babylone.jpg',
      'releaseDate' => self::PREORDER_RELEASE_DATE,
      'pages' => 232,
      'readingTime' => '5 h 30 de lecture',
      'language' => 'Français',
      'category' => 'Discernement spirituel · Prophétique',
      'themes' => ['Babylone', 'Discernement', 'Résistance', 'Fidélité'],
      'tagline' => 'Discerner l\'esprit de Babylone',
      'summary' => 'Ce livre naît d\'une urgence prophétique : l\'esprit de Babylone n\'est pas une réalité lointaine, mais une logique active qui séduit, normalise et s\'infiltre dans les pensées, les désirs et les choix du croyant.',
      'about' => [
        'À partir d\'Apocalypse 18:4 — « Sortez du milieu d\'elle, mon peuple » — Ken Luamba montre qu\'il est possible d\'appartenir à Dieu tout en laissant des influences extérieures façonner silencieusement sa vie intérieure.',
        'Le parcours conduit le lecteur du réveil prophétique aux glissements intérieurs, puis aux structures de rébellion, avant de s\'arrêter sur Daniel en territoire hostile comme modèle de fidélité sous pression.',
        'L\'appel à sortir n\'est pas une fuite physique du monde, mais une décision d\'appartenance : refuser les logiques qui contestent la seigneurie de Christ.',
      ],
      'formats' => [
        ['kind' => 'paper', 'label' => 'Édition imprimée', 'price' => 23, 'available' => true, 'detail' => 'Précommande · Couverture souple'],
        ['kind' => 'epub', 'label' => 'Édition numérique (EPUB)', 'price' => 13, 'available' => true, 'detail' => 'Précommande · Compatible liseuses & mobile'],
        ['kind' => 'audio', 'label' => 'Livre audio', 'price' => 17, 'available' => true, 'detail' => 'Précommande · Lu par l\'auteur'],
      ],
      'reviews' => [
        ['name' => 'Comité de lecture', 'role' => 'Éditions Philadelphie', 'rating' => 5, 'quote' => 'Un appel nécessaire au discernement et à la consécration.'],
      ],
      'excerpt' => [
        ['kind' => 'cover'],
        ['kind' => 'section', 'eyebrow' => 'I. Présentation et contexte du livre', 'title' => 'Une urgence prophétique', 'paragraphs' => [
          'Ce livre ne s\'inscrit pas dans une démarche de curiosité théologique. Il naît d\'une urgence prophétique.',
          'L\'auteur part d\'une conviction profonde : l\'esprit de Babylone n\'est pas une réalité lointaine réservée à des spécialistes ou à d\'autres générations. Il est actif, actuel, et profondément lié à la vie quotidienne de chaque croyant.',
        ]],
        ['kind' => 'section', 'eyebrow' => 'Présentation et contexte', 'title' => 'Sortez du milieu d\'elle, mon peuple', 'paragraphs' => [
          'Le point de départ est une parole d\'Apocalypse 18:4 — « Sortez du milieu d\'elle, mon peuple » — que l\'auteur analyse avec une précision remarquable.',
          'Cette parole ne s\'adresse pas aux inconvertis mais au peuple de Dieu lui-même, révélant une réalité troublante : il est possible d\'appartenir à Dieu tout en laissant des influences extérieures façonner silencieusement ses pensées, ses désirs et ses choix.',
        ]],
        ['kind' => 'part', 'eyebrow' => 'Première partie', 'title' => 'Réveil prophétique et discernement', 'paragraphs' => [
          'L\'auteur commence par poser les bases d\'un diagnostic prophétique. Une influence est déjà à l\'œuvre dans les mentalités, les systèmes et les normes du temps présent.',
          'L\'Église est appelée à passer d\'une foi réactive à une foi lucide, capable de lire le temps présent à la lumière de la Parole.',
        ]],
        ['kind' => 'part', 'eyebrow' => 'Deuxième partie', 'title' => 'Les glissements intérieurs', 'paragraphs' => [
          'Babylone ne reste pas extérieure : elle s\'installe dans l\'accoutumance. Ce qui dérangeait autrefois devient tolérable ; ce qui est toléré devient acceptable ; ce qui est devenu acceptable finit par être adopté.',
          'L\'auteur décrit avec précision le danger de la normalisation silencieuse, la désacralisation du sacré, et comment Babylone commence dans le cœur avant de se manifester dans les comportements.',
        ]],
        ['kind' => 'part', 'eyebrow' => 'Troisième partie', 'title' => 'Les structures de la rébellion', 'paragraphs' => [
          'Babel devient dans cette partie un contre-projet durable. L\'auteur analyse le refus du mandat divin, l\'inversion des principes, la spiritualité autonome et la confusion érigée en système.',
          'De Babel au système mondial, le parcours révèle comment ce qui commence par une rébellion individuelle devient une architecture spirituelle capable d\'absorber des nations et des institutions entières.',
        ]],
        ['kind' => 'part', 'eyebrow' => 'Quatrième partie', 'title' => 'Daniel en territoire hostile', 'paragraphs' => [
          'Le livre de Daniel devient ici le terrain d\'épreuve par excellence. L\'auteur y voit illustrée toute la mécanique de Babylone : l\'arrachement, la reprogrammation progressive, la pression sociale, la séduction de l\'apparence et la promesse du pouvoir.',
          'La résistance de Daniel et de ses compagnons — qui refusent les mets du roi — est présentée comme le modèle de la réponse du juste face à un système conçu pour effacer toute identité spirituelle.',
        ]],
        ['kind' => 'part', 'eyebrow' => 'Cinquième partie', 'title' => 'Le choix final de l\'Église', 'paragraphs' => [
          'Le parcours conduit à une décision claire et sans ambiguïté : face à la forme finale du système babylonien, l\'Église est appelée à sortir intérieurement.',
          'Non pas nécessairement par un retrait physique du monde, mais par un refus de la participation aux logiques qui contestent la seigneurie de Christ.',
        ]],
        ['kind' => 'backCover'],
      ],
    ];
  }
}
