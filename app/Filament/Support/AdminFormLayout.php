<?php

namespace App\Filament\Support;

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Utilitaires de mise en page des formulaires Filament admin.
 */
class AdminFormLayout
{
  /**
   * Applique la largeur pleine page au schéma de formulaire.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public static function fullWidth(Schema $schema): Schema
  {
    return $schema->columns(1);
  }

  /**
   * Crée une section encadrée avec grille responsive.
   *
   * @param string $heading Titre de la section
   * @param string|null $description Description courte
   * @param array<int, mixed> $fields Champs du formulaire
   * @param int $columns Nombre de colonnes desktop
   * @return Section Section Filament
   */
  public static function section(
    string $heading,
    ?string $description,
    array $fields,
    int $columns = 2,
  ): Section {
    return Section::make($heading)
      ->description($description)
      ->schema([
        Grid::make([
          'default' => 1,
          'md' => $columns,
          'xl' => $columns,
        ])->schema($fields),
      ])
      ->columnSpanFull()
      ->compact(false);
  }
}
