# Ken Luamba — Frontend (Next.js)

Boutique en ligne et espace membre du projet Ken Luamba.

> **Branche de déploiement :** `frontend-nextjs`  
> Cette branche contient uniquement le code Next.js à la racine, prêt pour Hostinger.

## Stack

| Composant | Version |
|-----------|---------|
| Next.js | 16 (App Router) |
| React | 19 |
| TypeScript | 5 |
| Tailwind CSS | 4 |

## Installation locale

```bash
npm install
cp .env.example .env.local
npm run dev
```

Le site est accessible sur [http://localhost:3000](http://localhost:3000).

## Variables d'environnement

| Variable | Description | Exemple |
|----------|-------------|---------|
| `NEXT_PUBLIC_API_URL` | URL de l'API Laravel | `http://localhost:8000/api/v1` |
| `NEXT_PUBLIC_APP_URL` | URL publique du frontend | `http://localhost:3000` |

## Déploiement Hostinger

### Option A — Node.js (recommandé)

Hostinger propose l'hébergement Node.js sur certains plans.

1. **Cloner la branche frontend**
   ```bash
   git clone -b frontend-nextjs <url-du-repo> .
   ```

2. **Installer et builder**
   ```bash
   npm install
   cp .env.example .env.local
   npm run build
   ```

3. **Configurer les variables** dans le panneau Hostinger :
   - `NEXT_PUBLIC_API_URL=https://api.kenluamba.com/api/v1`
   - `NEXT_PUBLIC_APP_URL=https://www.kenluamba.com`

4. **Démarrer l'application**
   ```bash
   npm run start
   ```
   Port par défaut : `3000` (configurer dans Hostinger).

### Option B — Export statique (pages limitées)

Si l'hébergement ne supporte pas Node.js, certaines pages peuvent être exportées en statique.  
Les routes dynamiques (panier, checkout, espace membre) nécessitent Node.js.

## Structure des routes (prévue)

```
/                    Accueil
/livres              Catalogue
/livres/[slug]       Fiche livre
/panier              Panier
/checkout            Paiement
/connexion           Auth OTP
/inscription         Inscription OTP
/espace/commandes    Suivi commandes (protégé)
/espace/livres       Bibliothèque numérique (protégé)
/livreur             Espace livreur (protégé)
```

## Lien avec le backend

Ce frontend consomme l'API de la branche `backend-filament-api`.  
Client API : `src/lib/api/client.ts`

## Scripts

| Commande | Description |
|----------|-------------|
| `npm run dev` | Serveur de développement |
| `npm run build` | Build production |
| `npm run start` | Serveur production |
| `npm run lint` | Vérification ESLint |
