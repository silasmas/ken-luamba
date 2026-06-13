# Livraison nationale — Villes, zones et communes

## Hiérarchie

```
Pays national (paramètres livraison)
  └── Villes (livraison oui/non)
        └── Zones tarifaires (par ville)
              └── Communes
```

1. **Paramètres livraison** : mode fixe ou par zone, politique internationale.
2. **Villes de livraison** : liste des villes couvertes ; activer/désactiver la livraison par ville.
3. **Zones de livraison** : tarif par zone, rattachée à une ville ; communes dans chaque zone.

## Admin Filament

| Menu | Rôle |
|------|------|
| Paramètres livraison | Mode national + international |
| **Villes de livraison** | Créer villes, toggle « Livraison disponible », zones depuis la fiche ville |
| Zones de livraison | Gérer zones et communes (vue globale) |

### Workflow recommandé

1. Créer les villes (Kinshasa, Lubumbashi, Goma…).
2. Activer **Livraison disponible** uniquement pour les villes desservies.
3. Depuis la fiche ville → onglet **Zones tarifaires** : créer les zones (Centre, Périphérie…).
4. Depuis **Zones de livraison** → éditer une zone → onglet **Communes** : ajouter les communes (la ville est renseignée automatiquement).

## API publique

### `GET /shipping/config`

Réponse enrichie :

```json
{
  "data": {
    "cities": [
      { "id": "…", "name": "Kinshasa", "isDeliveryAvailable": true }
    ],
    "zones": [
      {
        "id": "…",
        "name": "Kinshasa — Centre",
        "cityId": "…",
        "cityName": "Kinshasa",
        "amount": 5000,
        "communes": [{ "name": "Gombe", "city": "Kinshasa" }]
      }
    ]
  }
}
```

### `POST /shipping/quote`

Body national :

```json
{
  "fulfillmentType": "delivery",
  "country": "CD",
  "city": "Kinshasa",
  "commune": "Gombe"
}
```

Règles :

- `city` obligatoire en livraison nationale.
- La ville doit exister et avoir `isDeliveryAvailable = true`.
- Mode **zone** : `commune` obligatoire, recherche limitée aux zones de la ville choisie.
- Mode **fixe** : tarif national après validation de la ville.

Erreurs typiques :

| Champ | Message |
|-------|---------|
| `shippingAddress.city` | Ville non couverte ou livraison indisponible |
| `shippingAddress.commune` | Aucune zone pour cette commune dans la ville |

## Frontend checkout

1. Liste déroulante **villes** (uniquement `isDeliveryAvailable`).
2. Liste **communes** filtrée par ville (mode zone).
3. Devis recalculé à chaque changement ville/commune.

Fichiers : `frontend/src/lib/api/shipping.ts`, `frontend/src/app/checkout/page.tsx`.

## Modèles & tables

| Table | Modèle |
|-------|--------|
| `shipping_cities` | `ShippingCity` |
| `shipping_zones` | `ShippingZone` (`shipping_city_id`) |
| `shipping_zone_communes` | `ShippingZoneCommune` |

## Permissions Shield

Préfixe `ShippingCity` : ViewAny, View, Create, Update, Delete, etc.  
Seeder : `ShippingPermissionSeeder`.

## Seed de démonstration

`ShippingSettingSeeder` :

- Kinshasa : livraison active, 2 zones, communes exemple.
- Lubumbashi, Goma, Bukavu, Kisangani, Mbuji-Mayi : livraison désactivée (à activer depuis l’admin).
