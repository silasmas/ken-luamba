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
php artisan serve --port=8001
```

### Créer un administrateur Filament

```bash
php artisan make:filament-user
```

## Variables d'environnement

| Variable | Description |
|----------|-------------|
| `APP_URL` | URL publique du backend |
| `DEPLOY_SECRET` | Secret pour lancer les migrations via `GET /?secret=...` en production |
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

5. **Migrations et cache**
   ```bash
   php artisan migrate --force
   ```
   Ou via HTTP (sans SSH) une fois `DEPLOY_SECRET` défini :
   ```
   https://admin.kenluamba.com/?secret=VOTRE_DEPLOY_SECRET
   ```
   ```bash
   php artisan storage:link
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan filament:assets
   ```

6. **Permissions**
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

## Lien avec le frontend

Le frontend communique avec cette API via :

```env
NEXT_PUBLIC_API_URL=https://admin.kenluamba.com/api/v1
```

## Documentation

- Cahier des charges → [`docs/CAHIER-DES-CHARGES.md`](docs/CAHIER-DES-CHARGES.md)
- API → [`docs/API.md`](docs/API.md)
