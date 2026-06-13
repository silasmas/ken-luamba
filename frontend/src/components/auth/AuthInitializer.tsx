"use client";

import { useEffect } from "react";
import { useAuthStore } from "@/stores/authStore";

/**
 * Initialise l'authentification au chargement de l'application.
 */
export function AuthInitializer() {
  const initAuth = useAuthStore((state) => state.initAuth);

  useEffect(() => {
    void initAuth();
  }, [initAuth]);

  return null;
}
