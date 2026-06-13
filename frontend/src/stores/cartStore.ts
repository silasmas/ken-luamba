"use client";

import { create } from "zustand";
import { getStoredToken } from "@/lib/api/auth";
import {
  addCartItem,
  ensureCartSession,
  fetchCart,
  removeCartItem,
  updateCartItem,
} from "@/lib/api/cart";
import type { Cart } from "@/types/cart";

/**
 * État global du panier côté client.
 */
interface CartState {
  cart: Cart | null;
  isLoading: boolean;
  error: string | null;
  initCart: () => Promise<void>;
  addItem: (bookFormatId: string, quantity?: number) => Promise<void>;
  updateQuantity: (itemId: string, quantity: number) => Promise<void>;
  removeItem: (itemId: string) => Promise<void>;
}

/**
 * Résout le token utilisateur pour les requêtes panier authentifiées.
 *
 * @returns Token Sanctum ou undefined
 */
function resolveAuthToken(): string | undefined {
  return getStoredToken() ?? undefined;
}

/**
 * Store Zustand pour la gestion du panier.
 */
export const useCartStore = create<CartState>((set) => ({
  cart: null,
  isLoading: false,
  error: null,

  /**
   * Charge le panier depuis l'API (invité ou connecté).
   */
  initCart: async () => {
    set({ isLoading: true, error: null });

    try {
      const sessionId = await ensureCartSession();
      const response = await fetchCart(sessionId, resolveAuthToken());
      set({ cart: response.data, isLoading: false });
    } catch (error) {
      set({
        error: error instanceof Error ? error.message : "Erreur panier",
        isLoading: false,
      });
    }
  },

  /**
   * Ajoute un article au panier.
   *
   * @param bookFormatId Identifiant du format
   * @param quantity Quantité
   */
  addItem: async (bookFormatId: string, quantity = 1) => {
    set({ isLoading: true, error: null });

    try {
      const sessionId = await ensureCartSession();
      const response = await addCartItem(
        bookFormatId,
        sessionId,
        quantity,
        resolveAuthToken(),
      );
      set({ cart: response.data, isLoading: false });
    } catch (error) {
      set({
        error: error instanceof Error ? error.message : "Erreur ajout panier",
        isLoading: false,
      });
      throw error;
    }
  },

  /**
   * Met à jour la quantité d'un article.
   *
   * @param itemId Identifiant de ligne
   * @param quantity Nouvelle quantité
   */
  updateQuantity: async (itemId: string, quantity: number) => {
    set({ isLoading: true, error: null });

    try {
      const sessionId = await ensureCartSession();
      const response = await updateCartItem(
        itemId,
        quantity,
        sessionId,
        resolveAuthToken(),
      );
      set({ cart: response.data, isLoading: false });
    } catch (error) {
      set({
        error: error instanceof Error ? error.message : "Erreur mise à jour",
        isLoading: false,
      });
    }
  },

  /**
   * Supprime un article du panier.
   *
   * @param itemId Identifiant de ligne
   */
  removeItem: async (itemId: string) => {
    set({ isLoading: true, error: null });

    try {
      const sessionId = await ensureCartSession();
      const response = await removeCartItem(itemId, sessionId, resolveAuthToken());
      set({ cart: response.data, isLoading: false });
    } catch (error) {
      set({
        error: error instanceof Error ? error.message : "Erreur suppression",
        isLoading: false,
      });
    }
  },
}));
