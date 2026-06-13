"use client";

import { useCartStore } from "@/stores/cartStore";

/**
 * Badge affichant le nombre d'articles dans le panier.
 */
export function CartBadge() {
  const itemCount = useCartStore((state) => state.cart?.summary.itemCount ?? 0);

  if (itemCount === 0) {
    return null;
  }

  return (
    <span className="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-amber-600 px-1.5 py-0.5 text-xs font-semibold text-white">
      {itemCount}
    </span>
  );
}
