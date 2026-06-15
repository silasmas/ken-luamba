<?php

namespace App\Services\Books;

use Database\Seeders\Support\BookDashboardExportData;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Exporte les fiches livres en JSON pour import manuel en production.
 */
class BookDashboardExportService
{
  /**
   * Dossier relatif sur le disque local pour les exports.
   */
  public const EXPORT_DIRECTORY = 'exports/books';

  /**
   * Exporte tous les livres en fichiers JSON individuels.
   *
   * @return list<string> Chemins relatifs des fichiers générés
   */
  public function exportAll(): array
  {
    Storage::disk('local')->makeDirectory(self::EXPORT_DIRECTORY);

    $paths = [];

    foreach (BookDashboardExportData::books() as $slug => $book) {
      $paths[] = $this->exportBook($slug, $book);
    }

    $this->writeReadme();

    return $paths;
  }

  /**
   * Exporte un livre vers un fichier JSON.
   *
   * @param string $slug Identifiant du livre
   * @param array<string, mixed> $book Données du livre
   * @return string Chemin relatif du fichier
   */
  public function exportBook(string $slug, array $book): string
  {
    $relativePath = self::EXPORT_DIRECTORY.'/'.$slug.'.json';
    $json = json_encode($book, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '{}';

    Storage::disk('local')->put($relativePath, $json);

    return $relativePath;
  }

  /**
   * Écrit le guide d'import production.
   *
   * @return string Chemin relatif du README
   */
  public function writeReadme(): string
  {
    $readmePath = self::EXPORT_DIRECTORY.'/README-import-production.md';
    $absoluteExportDir = Storage::disk('local')->path(self::EXPORT_DIRECTORY);

    $content = <<<'MD'
# Import manuel des livres — Dashboard production

Chaque fichier `{slug}.json` regroupe **toutes les données** d'un livre, section par section, alignées sur le formulaire Filament.

## Ordre recommandé

1. **Auteur** : vérifier que Ken Luamba existe (seeder `AuthorSeeder` ou saisie manuelle).
2. **Livre** : Admin → Livres → Créer.
3. **Section Identification** : `sectionIdentification` du JSON.
4. **Section Contenu** : `sectionContenu` (coller les textes, un thème par ligne).
5. **Section Fiche éditoriale** : `sectionFicheEditoriale`.
6. **Visuels** : uploader depuis `ken-luamba-book-site/public/images/` les fichiers indiqués dans `sectionVisuels`.
7. **Aperçu feuilletable** : pour chaque entrée de `excerptPages`, ajouter une page dans le Repeater avec le `kind` et les champs correspondants.
8. **Test lecteur PDF (option 2)** : uploader le PDF généré par `php artisan books:generate-preview-pdfs`.

## Fichiers par livre

| Slug | JSON | PDF test |
|------|------|----------|
| eglise-face-aux-defis-de-lheure | eglise-face-aux-defis-de-lheure.json | books/previews/eglise-face-aux-defis-de-lheure.pdf |
| le-poids-du-silence | le-poids-du-silence.json | books/previews/le-poids-du-silence.pdf |
| generation-debout | generation-debout.json | books/previews/generation-debout.pdf |
| eglise-face-a-lesprit-de-babylone | eglise-face-a-lesprit-de-babylone.json | books/previews/eglise-face-a-lesprit-de-babylone.pdf |

## Seeders en production (alternative)

Page Admin → **Déploiement** → bouton **Seeders** → cocher uniquement `CatalogBookSeeder` (et `AuthorSeeder` si besoin).

URL HTTP : `/?secret=XXX&action=seed&class=CatalogBookSeeder`
MD;

    Storage::disk('local')->put($readmePath, $content);

    return $readmePath;
  }

  /**
   * Retourne le chemin absolu du dossier d'export.
   *
   * @return string Chemin absolu
   */
  public function absoluteExportDirectory(): string
  {
    return Storage::disk('local')->path(self::EXPORT_DIRECTORY);
  }
}
