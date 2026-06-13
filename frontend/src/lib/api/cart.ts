import { apiClient } from "@/lib/api/client";
import type { Cart, CartSessionResponse } from "@/types/cart";

const CART_SESSION_KEY = "ken-luamba-cart-session";

/**
 * Lit l'identifiant de session panier depuis le stockage local.
 *
 * @returns Session panier ou null
 */
export function getCartSessionId(): string | null {
  if (typeof window === "undefined") {
    return null;
  }

  return localStorage.getItem(CART_SESSION_KEY);
}

/**
 * Persiste l'identifiant de session panier.
 *
 * @param sessionId Identifiant de session
 * @returns void
 */
export function setCartSessionId(sessionId: string): void {
  localStorage.setItem(CART_SESSION_KEY, sessionId);
}

/**
 * Crée ou récupère une session panier invité.
 *
 * @returns Identifiant de session
 */
export async function ensureCartSession(): Promise<string> {
  const existing = getCartSessionId();

  if (existing) {
    return existing;
  }

  const response = await apiClient.post<CartSessionResponse>("/cart/session");
  setCartSessionId(response.sessionId);

  return response.sessionId;
}

/**
 * Récupère le panier courant.
 *
 * @param cartSession Identifiant de session
 * @param token Token utilisateur optionnel
 * @returns Panier avec totaux
 */
export async function fetchCart(cartSession: string, token?: string): Promise<{ data: Cart }> {
  return apiClient.get<{ data: Cart }>("/cart", { cartSession, token });
}

/**
 * Ajoute un format de livre au panier.
 *
 * @param bookFormatId Identifiant du format
 * @param cartSession Session panier
 * @param quantity Quantité à ajouter
 * @param token Token utilisateur optionnel
 * @returns Panier mis à jour
 */
export async function addCartItem(
  bookFormatId: string,
  cartSession: string,
  quantity = 1,
  token?: string,
): Promise<{ data: Cart }> {
  return apiClient.post<{ data: Cart }>(
    "/cart/items",
    { bookFormatId, quantity },
    { cartSession, token },
  );
}

/**
 * Met à jour la quantité d'une ligne panier.
 *
 * @param itemId Identifiant de ligne
 * @param quantity Nouvelle quantité
 * @param cartSession Session panier
 * @param token Token utilisateur optionnel
 * @returns Panier mis à jour
 */
export async function updateCartItem(
  itemId: string,
  quantity: number,
  cartSession: string,
  token?: string,
): Promise<{ data: Cart }> {
  return apiClient.patch<{ data: Cart }>(
    `/cart/items/${itemId}`,
    { quantity },
    { cartSession, token },
  );
}

/**
 * Supprime une ligne du panier.
 *
 * @param itemId Identifiant de ligne
 * @param cartSession Session panier
 * @param token Token utilisateur optionnel
 * @returns Panier mis à jour
 */
export async function removeCartItem(
  itemId: string,
  cartSession: string,
  token?: string,
): Promise<{ data: Cart }> {
  return apiClient.delete<{ data: Cart }>(`/cart/items/${itemId}`, {
    cartSession,
    token,
  });
}
