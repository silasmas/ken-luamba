"use client";

import Link from "next/link";
import { ShoppingBag } from "lucide-react";
import { cn } from "@/lib/utils";
import { CartBadge } from "@/components/cart/CartBadge";

/**
 * Lien panier toujours visible dans l'en-tête (desktop et mobile).
 */
export function HeaderCartLink({ className }: { className?: string }) {
  return (
    <Link
      href="/panier"
      aria-label="Panier"
      className={cn(
        "relative inline-flex items-center gap-2 rounded-full border border-ink/10 px-3 py-2 text-sm font-medium text-ink transition-colors hover:border-ink/25 hover:bg-ink/[0.03]",
        className,
      )}
    >
      <ShoppingBag className="h-4 w-4" />
      <span className="hidden sm:inline">Panier</span>
      <CartBadge />
    </Link>
  );
}
