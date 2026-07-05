<?php

namespace App\Filament\Support;

/**
 * Attributs communs pour les colonnes Filament redimensionnables.
 */
class ResizableTableColumn
{
  /**
   * Attributs d'en-tête et de cellule pour une colonne redimensionnable.
   *
   * @param string $key Identifiant stable de la colonne
   * @param string $defaultWidth Largeur CSS par défaut
   * @return array{header: array<string, string>, cell: array<string, string>} Attributs Filament
   */
  public static function attributes(string $key, string $defaultWidth = '18rem'): array
  {
    $widthRule = sprintf('width: var(--kl-col-%s, %s); min-width: 10rem; max-width: 42rem;', $key, $defaultWidth);

    return [
      'header' => [
        'data-kl-column' => $key,
        'class' => 'kl-resizable-th',
        'style' => $widthRule,
      ],
      'cell' => [
        'data-kl-column' => $key,
        'class' => 'align-top kl-resizable-td',
        'style' => $widthRule,
      ],
    ];
  }
}
