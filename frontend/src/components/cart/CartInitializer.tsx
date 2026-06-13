"use client";

import { useEffect } from "react";
import { useCartStore } from "@/stores/cartStore";

/**
 * Initialise le panier au chargement de l'application.
 */
export function CartInitializer() {
  const initCart = useCartStore((state) => state.initCart);

  useEffect(() => {
    initCart();
  }, [initCart]);

  return null;
}
