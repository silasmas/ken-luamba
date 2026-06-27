<?php

namespace App\Enums;

/**
 * Portée d'application d'une remise par quantité.
 */
enum DiscountAppliesTo: string
{
  case AllBooks = 'all_books';
  case SpecificBook = 'specific_book';
  case PhysicalOnly = 'physical_only';
  case SinglePhysicalTitle = 'single_physical_title';
  case DistinctPhysicalBooks = 'distinct_physical_books';
  case AuthorCompleteSet = 'author_complete_set';

  /**
   * Libellé affiché dans l'interface admin.
   *
   * @return string Libellé de la portée
   */
  public function label(): string
  {
    return match ($this) {
      self::DistinctPhysicalBooks => 'Pack — X livres différents',
      self::SinglePhysicalTitle => 'Même livre — X exemplaires d\'un titre',
      self::PhysicalOnly => 'Quantité totale — tous exemplaires physiques',
      self::AllBooks => 'Quantité totale — tous formats (y compris numériques)',
      self::SpecificBook => 'Livre précis — quantité sur un titre choisi',
      self::AuthorCompleteSet => 'Collection complète d\'un auteur',
    };
  }

  /**
   * Exemple concret affiché sous le sélecteur admin.
   *
   * @return string Description pédagogique du mode
   */
  public function adminExample(): string
  {
    return match ($this) {
      self::DistinctPhysicalBooks => 'Ex. seuil 4 : 1× livre A + 1× B + 1× C + 1× D = remise. 4× le même livre = pas de remise.',
      self::SinglePhysicalTitle => 'Ex. seuil 4 : 4× le même livre = remise. 2× A + 2× B = pas de remise (aucun titre seul n\'atteint 4).',
      self::PhysicalOnly => 'Ex. seuil 4 : 2× A + 2× B = remise (4 exemplaires au total, titres mélangés).',
      self::AllBooks => 'Comme la quantité totale physique, mais inclut aussi ebooks et audio dans le décompte.',
      self::SpecificBook => 'Seul le livre sélectionné ci-dessous est pris en compte (ex. 4× ce titre = remise).',
      self::AuthorCompleteSet => 'Remise si le panier contient au moins 1 exemplaire physique de chaque livre publié de l\'auteur.',
    };
  }

  /**
   * Options ordonnées pour le sélecteur Filament (modes principaux en tête).
   *
   * @return array<string, string> value => libellé
   */
  public static function adminSelectOptions(): array
  {
    $ordered = [
      self::DistinctPhysicalBooks,
      self::SinglePhysicalTitle,
      self::PhysicalOnly,
      self::SpecificBook,
      self::AllBooks,
      self::AuthorCompleteSet,
    ];

    $options = [];

    foreach ($ordered as $case) {
      $options[$case->value] = $case->label();
    }

    return $options;
  }
}
