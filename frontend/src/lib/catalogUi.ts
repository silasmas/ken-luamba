import type { BookSummary } from "@/types/catalog";
import { formatPrice } from "@/lib/formatPrice";

const ACCENTS = ["#1b1f2a", "#0f172a", "#1d2433", "#172554"];

/**
 * Retourne une couleur d'accent stable pour un slug livre.
 *
 * @param slug Identifiant URL du livre
 * @returns Code couleur hex
 */
export function accentForSlug(slug: string): string {
  let hash = 0;

  for (let index = 0; index < slug.length; index += 1) {
    hash = slug.charCodeAt(index) + ((hash << 5) - hash);
  }

  return ACCENTS[Math.abs(hash) % ACCENTS.length];
}

/**
 * Prix minimum d'un livre catalogue.
 *
 * @param book Résumé livre API
 * @returns Montant minimum ou null
 */
export function lowestBookPrice(book: BookSummary): number | null {
  const prices = book.formats
    ?.map((format) => format.currentPrice?.price)
    .filter(Boolean)
    .map((price) => parseFloat(price as string))
    .sort((a, b) => a - b);

  return prices?.[0] ?? null;
}

/**
 * Libellé prix catalogue.
 *
 * @param book Résumé livre
 * @param currency Devise
 * @returns Texte affichable
 */
export function bookPriceLabel(book: BookSummary, currency = "CDF"): string {
  const price = lowestBookPrice(book);

  if (price === null) {
    return "Prix à venir";
  }

  return `À partir de ${formatPrice(price, currency)}`;
}
