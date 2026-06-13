# Contenus numériques (Ebook / Audio) — Admin & restrictions

## Gestion Filament

Menu **Catalogue → Livres → [Livre] → onglet Formats**.

| Champ | Visible pour | Rôle |
|-------|--------------|------|
| Type de format | Tous | `ebook`, `audiobook`, `hardcover`, `paperback` |
| Type de fichier à télécharger | Formats **numériques** | `PDF`, `EPUB` (ebook) ou `MP3` (audio) |
| Stock | Formats **physiques** uniquement | Quantité en entrepôt |
| Fichier numérique | Formats **numériques** uniquement | Upload filtré selon le type choisi |

### Upload ebook

1. Créer un format de type **Ebook** ou **Audio**.
2. Choisir le **type de fichier** (PDF, EPUB ou MP3).
3. Section **Fichier numérique** → uploader le fichier correspondant (`books/digital/` sur disque privé `local`).
4. Le fichier n'est **jamais** exposé publiquement.

## Restrictions anti-partage

| Mécanisme | Détail |
|-----------|--------|
| Accès post-achat | `DigitalAccess` créé uniquement après paiement confirmé |
| URL signée | Lien temporaire (2 h par défaut, `DIGITAL_STREAM_EXPIRY_HOURS`) |
| Limite ouvertures | 5 téléchargements max par achat (`DIGITAL_MAX_DOWNLOADS`) |
| Route streaming | Signature Laravel + vérification propriétaire |
| Logs | Chaque ouverture enregistrée dans `digital_access_logs` |
| Pas de lien public | Fichier sur disque `local`, pas de CDN public |

### Affichage client (avant achat)

Sur la fiche livre, le client voit pour chaque format numérique :
- Le type de fichier (PDF / EPUB / MP3)
- Accès personnel lié au compte
- Durée de validité du lien
- Nombre max d'ouvertures
- Interdiction de partage

### Ce qui n'est pas encore appliqué

- Filigrane PDF personnalisé (champ `watermark` prévu, non injecté au flux)
- Lecteur web intégré (ouverture directe du fichier pour l'instant)

## Côté client

- **Ma bibliothèque** (`/espace/livres`) : liste des accès numériques
- Bouton **Lire / Télécharger** → URL signée one-time
- Un client ne voit que **ses** achats payés

## Formats — libellés

| Code API | Libellé client |
|----------|----------------|
| `hardcover` | Livre relié (couverture rigide) |
| `paperback` | Broché |
| `ebook` | Ebook |
| `audiobook` | Audio |

« Relié » désigne l'édition imprimée à couverture rigide (cartonnée), par opposition au broché (couverture souple).

## Fichiers clés

- `app/Filament/Resources/Books/RelationManagers/FormatsRelationManager.php`
- `app/Services/DigitalAccessService.php`
- `app/Http/Controllers/Api/V1/LibraryController.php`
- `routes/web.php` (route stream signée)
