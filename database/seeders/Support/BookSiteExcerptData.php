<?php

namespace Database\Seeders\Support;

/**
 * Extraits feuilletables alignés sur le book-site (books.ts).
 */
class BookSiteExcerptData
{
  /**
   * Retourne les pages d'aperçu pour un slug de livre.
   *
   * @param string $slug Identifiant URL du livre
   * @return list<array<string, mixed>> Pages de l'extrait
   */
  public static function forSlug(string $slug): array
  {
    return match ($slug) {
      'eglise-face-aux-defis-de-lheure' => self::egliseDefis(),
      'le-poids-du-silence', 'le-prix-du-sacrifice' => self::prixDuSacrifice(),
      'generation-debout', 'les-zones-sombres-du-coeur-humain' => self::zonesSombres(),
      'eglise-face-a-lesprit-de-babylone' => self::espritDeBabylone(),
      default => [
        ['kind' => 'cover'],
        ['kind' => 'backCover'],
      ],
    };
  }

  /**
   * Extrait — L'Église face aux défis de l'heure.
   *
   * @return list<array<string, mixed>>
   */
  private static function egliseDefis(): array
  {
    return [
      ['kind' => 'cover'],
      [
        'kind' => 'section',
        'eyebrow' => 'I. Présentation et contexte du livre',
        'title' => 'Un fardeau porté dans la prière',
        'paragraphs' => [
          'Ce livre est né d\'un fardeau. Pas d\'une réflexion théologique de cabinet, pas d\'une commande intellectuelle — mais d\'un poids porté dans la prière, d\'un cri intérieur pour l\'état de l\'Église de Jésus-Christ.',
          'Le Pasteur Ken Luamba écrit en sentinelle placée sur la muraille : non pour condamner, mais pour avertir ; non pour blesser, mais pour réveiller.',
        ],
      ],
      [
        'kind' => 'section',
        'eyebrow' => 'Présentation et contexte',
        'title' => 'Le diagnostic de départ',
        'paragraphs' => [
          'Le constat de départ est sévère mais juste : la foi se refroidit. La piété est devenue apparente plutôt que réelle. Le message exigeant de la croix laisse parfois place à un évangile centré sur le bien-être personnel.',
          'La sanctification est perçue comme une option et non comme une exigence. La crainte de Dieu s\'efface tandis que l\'immoralité s\'installe comme une norme acceptable.',
          'Face à ce diagnostic, l\'auteur ne se résigne pas. Il lance un appel solennel à la repentance sincère, à la radicalité spirituelle et à la consécration.',
        ],
      ],
      [
        'kind' => 'part',
        'eyebrow' => 'Première partie',
        'title' => 'Le diagnostic : une Église en crise',
        'paragraphs' => [
          'Les sept premiers chapitres dressent un état des lieux lucide de l\'Église contemporaine. Le premier défi identifié est la perte du discernement spirituel : il est possible d\'être actif sans être lucide, religieux sans être perspicace, d\'avancer sans direction.',
          'L\'auteur examine ensuite l\'Église tiraillée entre deux autels — celui de Dieu et celui du confort du monde — avant d\'analyser l\'oubli progressif de la mission évangélique, le paradoxe d\'une Église hyperconnectée mais spirituellement isolée, la séduction des fausses doctrines, l\'infiltration de l\'esprit de Babylone dans la génération actuelle, et enfin l\'apostasie rampante qui gagne du terrain dans le silence.',
        ],
      ],
      [
        'kind' => 'part',
        'eyebrow' => 'Deuxième partie',
        'title' => 'La réponse : restaurer, veiller, demeurer',
        'paragraphs' => [
          'L\'auteur ne s\'arrête pas au diagnostic. Il trace le chemin de la restauration.',
          'Le chapitre 8 appelle à la restauration de l\'autel de Dieu — revenir à la prière, à la consécration, à la vérité, à la croix.',
          'Le chapitre 9 est un appel aux veilleurs : ces hommes et ces femmes capables d\'entendre la voix de Dieu, de discerner les temps et de tenir leur position malgré l\'obscurité.',
        ],
      ],
      [
        'kind' => 'part',
        'eyebrow' => 'Troisième partie',
        'title' => 'La vision : l\'Église glorieuse',
        'paragraphs' => [
          'Malgré les compromis et malgré les ténèbres, l\'auteur clôt son livre sur une espérance intacte. L\'Église de Jésus-Christ ne sera jamais vaincue.',
          'Dieu prépare encore une Église : enracinée dans la Parole, conduite par l\'Esprit, fidèle jusqu\'à la fin, sanctifiée pour le retour du Seigneur.',
          'Une Église sans tache ni ride. Une Église qui préfère la vérité au compromis. Une Église qui porte encore le feu de Dieu.',
        ],
      ],
      ['kind' => 'backCover'],
    ];
  }

  /**
   * Extrait — Le Prix du Sacrifice.
   *
   * @return list<array<string, mixed>>
   */
  private static function prixDuSacrifice(): array
  {
    return [
      ['kind' => 'cover'],
      [
        'kind' => 'section',
        'eyebrow' => 'I. Présentation et contexte du livre',
        'title' => 'Retrouver le chemin de l\'autel',
        'paragraphs' => [
          '« Le Prix du Sacrifice » naît d\'un constat douloureux : quelque chose d\'essentiel a progressivement disparu de beaucoup de vies chrétiennes. L\'autel.',
          'Nous vivons dans une génération qui aime les promesses mais fuit les exigences. Une génération qui désire la gloire mais refuse la croix. Plusieurs veulent aller loin avec Dieu — sans rien perdre.',
        ],
      ],
      [
        'kind' => 'part',
        'eyebrow' => 'Première partie',
        'title' => 'L\'appel au sacrifice — Porter sa croix chaque jour',
        'paragraphs' => [
          'L\'auteur commence par établir que Jésus présente le sacrifice non comme une option spirituelle avancée réservée à une élite, mais comme la condition normale du discipolat.',
          'Trois réalités inséparables composent la réponse du disciple — le renoncement à soi-même, le port de la croix, et la marche à la suite de Christ.',
        ],
      ],
      ['kind' => 'backCover'],
    ];
  }

  /**
   * Extrait — Les Zones Sombres du Cœur Humain.
   *
   * @return list<array<string, mixed>>
   */
  private static function zonesSombres(): array
  {
    return [
      ['kind' => 'cover'],
      [
        'kind' => 'section',
        'eyebrow' => 'I. Présentation et contexte du livre',
        'title' => 'Caïn comme miroir du cœur',
        'paragraphs' => [
          '« Les Zones Sombres du Cœur Humain » est né d\'un enseignement sur Caïn — cet homme qui, par jalousie et haine silencieuse, finit par ôter la vie à son propre frère.',
          'Mais très vite, l\'auteur a compris que Caïn n\'est pas une exception : il est un miroir. C\'est pourquoi ce qui devait être un simple enseignement est devenu un livre.',
        ],
      ],
      [
        'kind' => 'part',
        'eyebrow' => 'Première partie',
        'title' => 'Le cœur, territoire caché de l\'homme',
        'paragraphs' => [
          'L\'auteur établit dès le départ la thèse centrale : Dieu regarde au cœur, pas à l\'apparence.',
          'Le cœur humain n\'est pas qu\'un centre émotionnel ; il est le véritable poste de commandement de toute la vie intérieure.',
        ],
      ],
      ['kind' => 'backCover'],
    ];
  }

  /**
   * Extrait — L'Église face à l'esprit de Babylone.
   *
   * @return list<array<string, mixed>>
   */
  private static function espritDeBabylone(): array
  {
    return [
      ['kind' => 'cover'],
      [
        'kind' => 'section',
        'eyebrow' => 'I. Présentation et contexte du livre',
        'title' => 'Une urgence prophétique',
        'paragraphs' => [
          'Ce livre ne s\'inscrit pas dans une démarche de curiosité théologique. Il naît d\'une urgence prophétique.',
          'L\'auteur part d\'une conviction profonde : l\'esprit de Babylone n\'est pas une réalité lointaine réservée à des spécialistes ou à d\'autres générations. Il est actif, actuel, et profondément lié à la vie quotidienne de chaque croyant.',
        ],
      ],
      [
        'kind' => 'section',
        'eyebrow' => 'Présentation et contexte',
        'title' => 'Sortez du milieu d\'elle, mon peuple',
        'paragraphs' => [
          'Le point de départ est une parole d\'Apocalypse 18:4 — « Sortez du milieu d\'elle, mon peuple » — que l\'auteur analyse avec une précision remarquable.',
          'Cette parole ne s\'adresse pas aux inconvertis mais au peuple de Dieu lui-même, révélant une réalité troublante : il est possible d\'appartenir à Dieu tout en laissant des influences extérieures façonner silencieusement ses pensées, ses désirs et ses choix.',
        ],
      ],
      [
        'kind' => 'part',
        'eyebrow' => 'Première partie',
        'title' => 'Réveil prophétique et discernement',
        'paragraphs' => [
          'L\'auteur commence par poser les bases d\'un diagnostic prophétique. Une influence est déjà à l\'œuvre dans les mentalités, les systèmes et les normes du temps présent.',
          'L\'Église est appelée à passer d\'une foi réactive à une foi lucide, capable de lire le temps présent à la lumière de la Parole.',
        ],
      ],
      [
        'kind' => 'part',
        'eyebrow' => 'Deuxième partie',
        'title' => 'Les glissements intérieurs',
        'paragraphs' => [
          'Babylone ne reste pas extérieure : elle s\'installe dans l\'accoutumance. Ce qui dérangeait autrefois devient tolérable ; ce qui est toléré devient acceptable ; ce qui est devenu acceptable finit par être adopté.',
          'L\'auteur décrit avec précision le danger de la normalisation silencieuse, la désacralisation du sacré, et comment Babylone commence dans le cœur avant de se manifester dans les comportements.',
        ],
      ],
      [
        'kind' => 'part',
        'eyebrow' => 'Troisième partie',
        'title' => 'Les structures de la rébellion',
        'paragraphs' => [
          'Babel devient dans cette partie un contre-projet durable. L\'auteur analyse le refus du mandat divin, l\'inversion des principes, la spiritualité autonome et la confusion érigée en système.',
          'De Babel au système mondial, le parcours révèle comment ce qui commence par une rébellion individuelle devient une architecture spirituelle capable d\'absorber des nations et des institutions entières.',
        ],
      ],
      [
        'kind' => 'part',
        'eyebrow' => 'Quatrième partie',
        'title' => 'Daniel en territoire hostile',
        'paragraphs' => [
          'Le livre de Daniel devient ici le terrain d\'épreuve par excellence. L\'auteur y voit illustrée toute la mécanique de Babylone : l\'arrachement, la reprogrammation progressive, la pression sociale, la séduction de l\'apparence et la promesse du pouvoir.',
          'La résistance de Daniel et de ses compagnons — qui refusent les mets du roi — est présentée comme le modèle de la réponse du juste face à un système conçu pour effacer toute identité spirituelle.',
        ],
      ],
      [
        'kind' => 'part',
        'eyebrow' => 'Cinquième partie',
        'title' => 'Le choix final de l\'Église',
        'paragraphs' => [
          'Le parcours conduit à une décision claire et sans ambiguïté : face à la forme finale du système babylonien, l\'Église est appelée à sortir intérieurement.',
          'Non pas nécessairement par un retrait physique du monde, mais par un refus de la participation aux logiques qui contestent la seigneurie de Christ.',
        ],
      ],
      ['kind' => 'backCover'],
    ];
  }
}
