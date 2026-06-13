# API Ken Luamba — v1

Base URL : `http://localhost:8001/api/v1`

## Catalogue (public)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/health` | Santé de l'API |
| GET | `/books` | Liste des livres publiés |
| GET | `/books/{slug}` | Détail d'un livre |
| GET | `/authors/{slug}` | Profil auteur |
| GET | `/pickup-points` | Points de retrait actifs |
| GET | `/shipping/config` | Paramètres livraison + villes + zones |
| POST | `/shipping/quote` | Devis frais de livraison |
| GET | `/payments/mobile-providers` | Opérateurs Mobile Money (RDC) |

## Panier

Header invité : `X-Cart-Session: {sessionId}`

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/cart/session` | Créer une session panier |
| GET | `/cart` | Consulter le panier |
| POST | `/cart/items` | Ajouter un article (`bookFormatId`, `quantity`) |
| PATCH | `/cart/items/{id}` | Modifier la quantité |
| DELETE | `/cart/items/{id}` | Retirer un article |

## Authentification OTP

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/auth/register` | Inscription — envoi OTP (`email`, `fullName`) |
| POST | `/auth/login` | Connexion — envoi OTP (`email`) |
| POST | `/auth/verify-otp` | Vérification (`email`, `code`, `type`) → token Sanctum |
| GET | `/auth/me` | Profil (Bearer token) |
| PATCH | `/auth/me` | Modifier profil et adresses |
| POST | `/auth/me/avatar` | Photo de profil (multipart) |
| POST | `/auth/logout` | Déconnexion |

## Commandes & paiements (auth requise)

Header : `Authorization: Bearer {token}` et `X-Cart-Session` pour créer une commande.

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/orders` | Créer commande depuis panier |
| GET | `/orders` | Mes commandes |
| GET | `/orders/{orderNumber}` | Détail commande |
| POST | `/orders/{orderNumber}/pay` | Payer — voir ci-dessous |
| GET | `/payments/status?reference=` | Polling statut paiement (2 s recommandé) |
| GET | `/payments/card-return?reference=&status=` | Retour paiement carte |
| POST | `/payments/flexpay-callback` | Webhook passerelle (interne) |

### Paiement Mobile Money

Body :

```json
{
  "channel": "mobile_money",
  "providerCode": "orange",
  "phone": "243891234567"
}
```

- `providerCode` : `mpesa`, `orange`, `airtel`, `afri` (liste via `/payments/mobile-providers`)
- Le numéro doit correspondre à l'opérateur choisi (validation serveur)
- Réponse inclut `steps[]` pour affichage sous le bouton « Payer »
- **UI client : ne pas mentionner FlexPay** — utiliser le libellé opérateur

Guide UX complet : [`PAIEMENT-MOBILE-MONEY-UX.md`](PAIEMENT-MOBILE-MONEY-UX.md)

### Livraison nationale

- `city` obligatoire ; communes filtrées par ville (mode zone)
- Villes avec `isDeliveryAvailable` dans `/shipping/config`
- Guide : [`LIVRAISON-VILLES.md`](LIVRAISON-VILLES.md)

### Passerelle technique (backend uniquement)

- Configuration : `docs/integration-paiement-flexpay/`
- Mobile Money : `type = "1"` côté passerelle
- Variables `.env` : `FLEXPAY_API_TOKEN`, `FLEXPAY_MARCHAND`, gateways

## Phase 5 — Livraison & contenus numériques (auth requise)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/orders/{orderNumber}/confirm-receipt` | Client confirme réception |
| POST | `/orders/{orderNumber}/dispute-delivery` | Client conteste livraison |
| GET | `/library` | Bibliothèque ebook/audio |
| GET | `/library/{accessId}/stream` | URL signée de lecture |
| GET | `/courier/deliveries` | Livraisons assignées (livreur) |
| POST | `/courier/scan` | Scanner token QR |
| POST | `/courier/deliveries/{id}/accept` | Livreur prend une course |
| POST | `/courier/confirm` | Confirmer livraison/retrait (+ photo) |

Guide livreur & QR : [`LIVRAISON-LIVREUR.md`](LIVRAISON-LIVREUR.md)

Guide : [`CONTENUS-NUMERIQUES.md`](CONTENUS-NUMERIQUES.md)

## Admin Filament

- URL : `http://localhost:8001/admin`
- Menu **Ventes** : Commandes, Paiements, Livraisons, Points de retrait, **Paramètres livraison**, **Villes de livraison**, **Zones de livraison**
- Menu **Système** : Utilisateurs, Documentation API
