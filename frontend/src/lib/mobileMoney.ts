/**
 * Opérateur Mobile Money exposé par l'API.
 */
export interface MobileMoneyOperator {
  code: string;
  label: string;
  msisdnPattern: string;
  phoneHint: string;
}

/**
 * Étape affichée pendant le paiement Mobile Money.
 */
export interface PaymentStep {
  id: string;
  label: string;
  status: "pending" | "active" | "done" | "error";
}

/**
 * Vérifie si un numéro correspond à l'opérateur sélectionné.
 *
 * @param phone Numéro 243XXXXXXXXX
 * @param operator Opérateur choisi
 * @returns Message d'erreur ou null si valide
 */
export function validateMobileMoneyPhone(
  phone: string,
  operator: MobileMoneyOperator | null,
): string | null {
  if (!/^243[0-9]{9}$/.test(phone)) {
    return "Utilisez le format 243XXXXXXXXX (12 chiffres).";
  }

  if (!operator?.msisdnPattern) {
    return null;
  }

  const regex = new RegExp(operator.msisdnPattern);

  if (!regex.test(phone)) {
    return `Ce numéro ne correspond pas à ${operator.label}.`;
  }

  return null;
}

/**
 * Normalise un numéro saisi (supprime espaces, préfixe 243).
 *
 * @param value Saisie utilisateur
 * @returns Numéro normalisé
 */
export function normalizeMobileMoneyPhone(value: string): string {
  const digits = value.replace(/\D/g, "");

  if (digits.startsWith("243")) {
    return digits.slice(0, 12);
  }

  if (digits.startsWith("0") && digits.length === 10) {
    return `243${digits.slice(1)}`;
  }

  if (digits.length === 9) {
    return `243${digits}`;
  }

  return digits.slice(0, 12);
}
