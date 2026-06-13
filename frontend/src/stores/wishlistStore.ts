"use client";

import { create } from "zustand";

const WISHLIST_KEY = "ken-luamba-wishlist";

/**
 * État local de la liste de souhaits (stockage navigateur).
 */
interface WishlistState {
  bookSlugs: string[];
  isReady: boolean;
  initWishlist: () => void;
  toggleBook: (slug: string) => boolean;
  isInWishlist: (slug: string) => boolean;
}

/**
 * Lit les slugs enregistrés dans le localStorage.
 *
 * @returns Liste des slugs
 */
function readWishlist(): string[] {
  if (typeof window === "undefined") {
    return [];
  }

  try {
    const raw = localStorage.getItem(WISHLIST_KEY);
    const parsed = raw ? (JSON.parse(raw) as string[]) : [];

    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
}

/**
 * Persiste la liste de souhaits.
 *
 * @param slugs Slugs des livres
 */
function writeWishlist(slugs: string[]): void {
  localStorage.setItem(WISHLIST_KEY, JSON.stringify(slugs));
}

/**
 * Store Zustand pour la liste de souhaits côté client.
 */
export const useWishlistStore = create<WishlistState>((set, get) => ({
  bookSlugs: [],
  isReady: false,

  /**
   * Charge la liste depuis le localStorage au démarrage.
   */
  initWishlist: () => {
    set({ bookSlugs: readWishlist(), isReady: true });
  },

  /**
   * Ajoute ou retire un livre de la liste.
   *
   * @param slug Slug du livre
   * @returns true si ajouté, false si retiré
   */
  toggleBook: (slug: string) => {
    const current = get().bookSlugs;
    const exists = current.includes(slug);
    const next = exists ? current.filter((item) => item !== slug) : [...current, slug];

    writeWishlist(next);
    set({ bookSlugs: next });

    return !exists;
  },

  /**
   * Indique si un livre est dans la liste.
   *
   * @param slug Slug du livre
   */
  isInWishlist: (slug: string) => {
    return get().bookSlugs.includes(slug);
  },
}));
