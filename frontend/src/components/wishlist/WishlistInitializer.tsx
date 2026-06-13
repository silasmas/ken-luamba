"use client";

import { useEffect } from "react";
import { useWishlistStore } from "@/stores/wishlistStore";

/**
 * Initialise la liste de souhaits au chargement de l'application.
 */
export function WishlistInitializer() {
  const initWishlist = useWishlistStore((state) => state.initWishlist);

  useEffect(() => {
    initWishlist();
  }, [initWishlist]);

  return null;
}
