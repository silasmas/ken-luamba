"use client";

import { useEffect, useState } from "react";
import { useCartStore } from "@/stores/cartStore";

/**
 * Badge affichant le nombre d'articles dans le panier.
 * Attend le montage client pour éviter les erreurs d'hydratation.
 */
export function CartBadge() {
  const itemCount = useCartStore((state) => state.cart?.summary.itemCount ?? 0);
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
  }, []);

  const count = mounted ? itemCount : 0;

  return (
    <span
      className={
        count > 0
          ? "ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-amber-600 px-1.5 py-0.5 text-xs font-semibold text-white"
          : "ml-1 inline-flex min-w-5 items-center justify-center rounded-full border border-ink/15 px-1.5 py-0.5 text-[0.65rem] font-medium text-ink/45"
      }
    >
      {count}
    </span>
  );
}
