# Ken Luamba — Backend (Laravel + Filament + API)

Backend du projet Ken Luamba : API REST et back-office Filament.

> **Branche de déploiement :** `backend-filament-api`  
> Cette branche contient uniquement le code Laravel à la racine, prêt pour Hostinger.

## Stack

| Composant | Version |
|-----------|---------|
| PHP | 8.3+ |
| Laravel | 13 |
| Filament | 5 |
| Sanctum | 4 |

## URLs

| Service | Chemin local | Production (exemple) |
|---------|--------------|----------------------|
| API | `http://localhost:8000/api/v1` | `https://api.kenluamba.com/api/v1` |
| Back-office | `http://localhost:8000/admin` | `https://api.kenluamba.com/admin` |
| Health check | `http://localhost:8000/api/v1/health` | — |

## Installation locale

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
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

### Prérequis hébergement

- PHP 8.3 ou supérieur
- Extensions : `openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`
- Composer disponible (SSH)
- MySQL ou PostgreSQL

### Étapes

1. **Cloner la branche backend**
   ```bash
   git clone -b backend-filament-api <url-du-repo> .
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
   Ou via HTTP (sans SSH) une fois `DEPLOY_SECRET` défini dans `.env` :
   ```
   https://api.kenluamba.com/?secret=VOTRE_DEPLOY_SECRET
   ```
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan filament:assets
   ```

6. **Permissions**
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

7. **Créer l'admin**
   ```bash
   php artisan make:filament-user
   ```

### Cron (queues et tâches planifiées)

```cron
* * * * * cd /chemin/vers/projet && php artisan schedule:run >> /dev/null 2>&1
```

## Structure API (v1)

```
/api/v1/health          GET   — Santé de l'API
/api/v1/auth/*          POST  — Authentification OTP (à venir)
/api/v1/books           GET   — Catalogue (à venir)
/api/v1/cart/*          *     — Panier (à venir)
/api/v1/orders/*        *     — Commandes (à venir)
```

## Lien avec le frontend

Le frontend Next.js (branche `frontend-nextjs`) communique avec cette API via `NEXT_PUBLIC_API_URL`.
