# Ken Luamba — Backend (Laravel + Filament + API)

Backend du projet Ken Luamba : API REST et back-office Filament.

> **Repo frontend (boutique Next.js)** : [silasmas/kenluamba_front](https://github.com/silasmas/kenluamba_front)  
> **Déploiement** : `admin.kenluamba.com` → document root `public/`

## Stack

| Composant | Version |
|-----------|---------|
| PHP | 8.3+ |
| Laravel | 13 |
| Filament | 5 |
| Sanctum | 4 |

## URLs

| Service | Chemin local | Production |
|---------|--------------|------------|
| API | `http://localhost:8001/api/v1` | `https://admin.kenluamba.com/api/v1` |
| Back-office | `http://localhost:8001/admin` | `https://admin.kenluamba.com/admin` |
| Health check | `http://localhost:8001/api/v1/health` | — |

## Installation locale

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve --port=8001
```

### Créer un administrateur Filament

```bash
php artisan make:filament-user
```

## Variables d'environnement

| Variable | Description |
|----------|-------------|
| `APP_URL` | URL publique du backend (ex. `https://admin.kenluamba.com`) |
| `FILESYSTEM_DISK` | Doit être `public` pour les images catalogue et avatars |
| `DEPLOY_SECRET` | Secret pour les actions de déploiement HTTP (`migrate`, `seed`, `setup`, `shield`) |
| `FRONTEND_URL` | URL du frontend Next.js (CORS) |
| `SANCTUM_STATEFUL_DOMAINS` | Domaines autorisés pour Sanctum |
| `DB_*` | Configuration base de données |

## Déploiement Hostinger

### Étapes

1. **Cloner ce dépôt**
   ```bash
   git clone https://github.com/silasmas/ken-luamba.git .
   ```

2. **Installer les dépendances**
   ```bash
   composer install --optimize-autoloader --no-dev
   ```

3. **Configurer l'environnement**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Renseigner `APP_URL`, `DB_*`, `FRONTEND_URL`, `SANCTUM_STATEFUL_DOMAINS`.

4. **Document root**
   Pointer le domaine vers le dossier `public/` (obligatoire Laravel).

5. **Migrations, seeders et cache**
   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   ```
   Ou via HTTP (sans SSH) une fois `DEPLOY_SECRET` et la base MySQL configurés :
   ```
   https://admin.kenluamba.com/?secret=VOTRE_DEPLOY_SECRET
   https://admin.kenluamba.com/?secret=VOTRE_DEPLOY_SECRET&action=seed
   https://admin.kenluamba.com/?secret=VOTRE_DEPLOY_SECRET&action=setup
   https://admin.kenluamba.com/?secret=VOTRE_DEPLOY_SECRET&action=shield
   https://admin.kenluamba.com/?secret=VOTRE_DEPLOY_SECRET&action=storage
   ```
   | Action | URL | Effet |
   |--------|-----|-------|
   | `migrate` | `?secret=...` | Migrations uniquement |
   | `seed` | `?secret=...&action=seed` | Données initiales (auteur, livres, admin, livreur…) |
   | `setup` | `?secret=...&action=setup` | Migrations + seeders + lien `storage` |
   | `shield` | `?secret=...&action=shield` | Permissions Filament Shield + droits livraison |
   | `storage` | `?secret=...&action=storage` | Crée le lien `public/storage` (obligatoire pour afficher les images) |

   Ordre recommandé au premier déploiement : `setup` puis `shield`.

   **Alternative admin (avec connexion super_admin)** : menu **Système → Déploiement** dans Filament — boutons pour migrations, Shield, lien storage, seeders et setup complet.

6. **Permissions**
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

## Module Déploiement (admin)

Menu **Système → Déploiement** (`/admin/system-deployment`) — réservé au rôle `super_admin`.

| Bouton | Commande équivalente | Usage |
|--------|---------------------|-------|
| Migrations | `php artisan migrate --force` | Met à jour la base |
| Permissions Shield | `shield:generate` + super admin | Droits Filament |
| Lien storage | `php artisan storage:link --force` | Images `/storage` |
| Seeders | `php artisan db:seed --force` | Données initiales |
| Setup complet | migrate + seed + storage | Premier déploiement |

## Module Invitations (événements)

Gestion des invités pour cérémonies de publication, lancements de livres et autres événements.

### Admin Filament

| Menu | Rôle |
|------|------|
| **Événements → Événements** | Créer l'événement (date, lieu, livre associé, message d'accueil) |
| **Événements → Invitations** | Gérer les invités et envoyer les invitations |
| Onglet **Invités** (sur un événement) | Ajouter des invités directement à l'événement |

### Envoi aux invités

Par invité, actions disponibles :

- **Email** — envoi automatique avec lien de réponse
- **WhatsApp** — ouvre wa.me avec message prérempli
- **SMS** — ouvre l'app SMS avec message prérempli
- **Lien** — affiche l'URL d'invitation à copier

Envoi **email en masse** via sélection multiple.

### Page publique invité

URL envoyée dans le message :

```
{FRONTEND_URL}/invitation/{token}
```

L'invité peut :

1. Voir les détails de l'événement
2. Confirmer **Présent** ou **Absent**
3. Laisser un **mot optionnel**

### API publique

```
GET  /api/v1/invitations/{token}
POST /api/v1/invitations/{token}/rsvp
```

### Étapes — premier événement

1. Admin → **Système → Déploiement** → **Setup complet** puis **Permissions Shield** (si première install)
2. Admin → **Événements** → créer un événement (ex. cérémonie de lancement)
3. Onglet **Invités** → ajouter nom, email et/ou téléphone
4. Envoyer via **Email**, **WhatsApp** ou **SMS**
5. L'invité ouvre le lien → répond sur la page publique
6. Suivre les réponses dans **Invitations** (colonne RSVP)

## Lien avec le frontend

Le frontend communique avec cette API via :

```env
NEXT_PUBLIC_API_URL=https://admin.kenluamba.com/api/v1
```

## Documentation

- Cahier des charges → [`docs/CAHIER-DES-CHARGES.md`](docs/CAHIER-DES-CHARGES.md)
- API → [`docs/API.md`](docs/API.md)
