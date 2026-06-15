# Import livres — données books.ts

Fichiers générés par `php artisan books:export-dashboard-data` dans l'ordre du book-site.

| # | Livre | JSON | PDF à uploader |
|---|-------|------|----------------|
| 1 | L'Église face aux défis de l'heure | `01-eglise-face-aux-defis-de-lheure.json` | `01-eglise-face-aux-defis-de-lheure-extrait.pdf` |
| 2 | Le Prix du Sacrifice | `02-le-poids-du-silence.json` | `02-le-poids-du-silence-extrait.pdf` |
| 3 | Les Zones Sombres du Cœur Humain | `03-generation-debout.json` | `03-generation-debout-extrait.pdf` |
| 4 | L'Église face à l'esprit de Babylone | `04-eglise-face-a-lesprit-de-babylone.json` | `04-eglise-face-a-lesprit-de-babylone-extrait.pdf` |

## Dashboard Filament

1. Exécuter `AuthorSeeder` puis `CatalogBookSeeder` (extraits + textes déjà en base)
2. Uploader **couverture recto** et **quatrième de couverture** dans chaque livre
3. Option PDF : uploader le fichier `-extrait.pdf` dans **Extrait PDF**
