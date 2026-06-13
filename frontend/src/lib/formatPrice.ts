/**
 * Formate un montant en devise lisible.
 *
 * @param amount Montant numérique
 * @param currency Code devise
 * @returns Chaîne formatée
 */
export function formatPrice(amount: number | string, currency = "CDF"): string {
  const value = typeof amount === "string" ? parseFloat(amount) : amount;

  return new Intl.NumberFormat("fr-FR", {
    style: "currency",
    currency,
    maximumFractionDigits: 0,
  }).format(value);
}
