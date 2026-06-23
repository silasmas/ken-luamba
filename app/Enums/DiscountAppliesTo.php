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
  case AuthorCompleteSet = 'author_complete_set';

  /**
   * Libellé affiché dans l'interface admin.
   *
   * @return string Libellé de la portée
   */
  public function label(): string
  {
    return match ($this) {
      self::AllBooks => 'Tous les livres',
      self::SpecificBook => 'Livre spécifique',
      self::PhysicalOnly => 'Livres physiques uniquement',
      self::AuthorCompleteSet => 'Collection complète d\'un auteur',
    };
  }
}
