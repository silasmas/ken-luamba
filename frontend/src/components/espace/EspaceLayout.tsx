"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { cn } from "@/lib/utils";

const NAV_ITEMS = [
  { href: "/espace/commandes", label: "Mes commandes" },
  { href: "/espace/livres", label: "Ma bibliothèque" },
  { href: "/espace/profil", label: "Mon profil" },
];

/**
 * Layout de navigation pour l'espace membre — style éditorial.
 *
 * @param children Contenu de la page
 */
export function EspaceLayout({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();

  return (
    <div className="mx-auto max-w-7xl px-6 py-10 lg:px-10">
      <nav className="mb-8 flex flex-wrap gap-2 border-b border-ink/[0.08] pb-4">
        {NAV_ITEMS.map((item) => (
          <Link
            key={item.href}
            href={item.href}
            className={cn(
              "rounded-full px-4 py-2 text-sm font-medium transition-colors",
              pathname.startsWith(item.href)
                ? "bg-ink text-paper"
                : "bg-ink/[0.04] text-ink/70 hover:text-ink",
            )}
          >
            {item.label}
          </Link>
        ))}
      </nav>
      {children}
    </div>
  );
}
