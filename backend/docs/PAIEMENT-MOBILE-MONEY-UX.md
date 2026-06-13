# Paiement Mobile Money — expérience client (UX)

> Guide pour toute implémentation frontend (Next.js, Blade, mobile).  
> **Ne jamais afficher le nom de la passerelle** (FlexPay) au client : parler d’**opérateur** (M-Pesa, Orange Money, etc.).

## Principes

| Règle | Détail |
|-------|--------|
| Vocabulaire client | « Opérateur », « Mobile Money », « Validez sur votre téléphone » |
| Vocabulaire interdit (UI) | FlexPay, passerelle technique, `type` API |
| Sélection opérateur | Obligatoire avant paiement — liste via API |
| Validation numéro | Côté client **et** serveur (`providerCode` + `phone`) |
| Étapes visibles | Sous le bouton « Payer », pas au-dessus |
| Polling | Toutes les **2 secondes**, première vérification immédiate |

---

## API publique

### Liste des opérateurs

```
GET /api/v1/payments/mobile-providers
```

Réponse :

```json
{
  "data": [
    {
      "code": "orange",
      "label": "Orange Money",
      "msisdnPattern": "^2438[459][0-9]{7}$",
      "phoneHint": "24384/85/89XXXXXXX"
    }
  ]
}
```

### Initier un paiement Mobile Money

```
POST /api/v1/orders/{orderNumber}/pay
Authorization: Bearer {token}

{
  "channel": "mobile_money",
  "providerCode": "orange",
  "phone": "243891234567"
}
```

Réponse (extrait) :

```json
{
  "data": {
    "type": "mobile_money",
    "message": "Une demande de paiement a été envoyée à Orange Money...",
    "operatorLabel": "Orange Money",
    "steps": [
      { "id": "order", "label": "Commande enregistrée", "status": "done" },
      { "id": "request", "label": "Demande envoyée à Orange Money", "status": "done" },
      { "id": "confirm", "label": "Confirmez le paiement sur votre téléphone", "status": "active" },
      { "id": "verify", "label": "Vérification du paiement", "status": "pending" }
    ]
  }
}
```

### Polling statut

```
GET /api/v1/payments/status?reference={orderNumber}
```

| `status` | Signification | Action UI |
|----------|---------------|-----------|
| `0` | Payé | Rediriger vers page succès |
| `1` | Annulé / refusé | Afficher erreur, arrêter polling |
| `2` | En attente | Continuer polling, mettre à jour `steps` |

Intervalle recommandé : **2000 ms**, max ~45 tentatives (90 s).

---

## Étapes affichées au client

1. **Enregistrement de la commande**
2. **Envoi de la demande à l’opérateur** (M-Pesa, Orange…)
3. **Confirmation sur le téléphone** (PIN / USSD)
4. **Vérification du paiement**

Statuts d’une étape : `pending` | `active` | `done` | `error`

Composant frontend : `frontend/src/components/checkout/PaymentSteps.tsx`

---

## Validation numéro par opérateur

Configuration : `config/flexpay.php` → `flexpay_mobile_providers`

| Opérateur | Regex MSISDN (exemple) |
|-----------|--------------------------|
| M-Pesa | `^2438[123][0-9]{7}$` |
| Orange Money | `^2438[459][0-9]{7}$` |
| Airtel Money | `^24399[0-9]{7}$` |
| Afri Money | `^24390[0-9]{7}$` |

Le backend valide via `MobileMoneyOperatorService`. Le frontend réutilise `msisdnPattern` de l’API.

**Important :** le champ `type` envoyé à la passerelle technique reste toujours `"1"` pour tout Mobile Money — voir `docs/integration-paiement-flexpay/08-MOBILE-MONEY-CORRECTIFS.md`.

---

## Fichiers de référence (projet Ken Luamba)

| Couche | Fichier |
|--------|---------|
| Opérateurs | `app/Services/MobileMoneyOperatorService.php` |
| Paiement | `app/Services/PaymentService.php` |
| Checkout | `frontend/src/app/checkout/page.tsx` |
| Helpers UI | `frontend/src/lib/mobileMoney.ts` |
| Config opérateurs | `config/flexpay.php` |

---

## Checklist nouvelle implémentation

- [ ] Charger les opérateurs via `GET /payments/mobile-providers`
- [ ] Afficher la sélection opérateur (cartes / radio)
- [ ] Valider le numéro avec `msisdnPattern` avant envoi
- [ ] Envoyer `providerCode` + `phone` au backend
- [ ] Afficher les **étapes sous le bouton Payer**
- [ ] Polling 2 s avec gestion `status` 0 / 1 / 2
- [ ] Aucune mention FlexPay dans l’interface client
