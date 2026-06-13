"use client";

import { create } from "zustand";
import { fetchMe, getStoredToken, logout as apiLogout } from "@/lib/api/auth";
import { clearAuthToken } from "@/lib/authToken";
import type { User } from "@/types/auth";

/**
 * État global d'authentification côté client.
 */
interface AuthState {
  user: User | null;
  token: string | null;
  isLoading: boolean;
  isReady: boolean;
  error: string | null;
  initAuth: () => Promise<void>;
  setSession: (token: string, user: User) => void;
  setUser: (user: User) => void;
  logout: () => Promise<void>;
}

/**
 * Store Zustand pour l'authentification OTP.
 */
export const useAuthStore = create<AuthState>((set, get) => ({
  user: null,
  token: null,
  isLoading: false,
  isReady: false,
  error: null,

  /**
   * Charge le profil si un token est stocké.
   */
  initAuth: async () => {
    const token = getStoredToken();

    if (!token) {
      set({ isReady: true });
      return;
    }

    set({ isLoading: true, error: null });

    try {
      const user = await fetchMe(token);
      set({ user, token, isLoading: false, isReady: true });
    } catch {
      clearAuthToken();
      set({ user: null, token: null, isLoading: false, isReady: true });
    }
  },

  /**
   * Enregistre la session après OTP validé.
   *
   * @param token Token Sanctum
   * @param user Profil utilisateur
   */
  setSession: (token: string, user: User) => {
    set({ token, user, error: null, isReady: true });
  },

  /**
   * Met à jour le profil en mémoire.
   *
   * @param user Profil utilisateur
   */
  setUser: (user: User) => {
    set({ user });
  },

  /**
   * Déconnecte l'utilisateur.
   */
  logout: async () => {
    const { token } = get();

    if (token) {
      try {
        await apiLogout(token);
      } catch {
        // Token déjà invalide
      }
    }

    set({ user: null, token: null });
  },
}));
