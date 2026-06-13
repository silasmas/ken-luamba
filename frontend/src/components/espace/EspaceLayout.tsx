"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

const NAV_ITEMS = [
  { href: "/espace/commandes", label: "Mes commandes" },
  { href: "/espace/livres", label: "Ma bibliothèque" },
  { href: "/espace/profil", label: "Mon profil" },
];

/**
 * Layout de navigation pour l'espace membre.
 *
 * @param children Contenu de la page
 */
export function EspaceLayout({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();

  return (
    <div className="mx-auto max-w-5xl">
      <nav className="mb-8 flex flex-wrap gap-2 border-b border-stone-200 pb-4">
        {NAV_ITEMS.map((item) => (
          <Link
            key={item.href}
            href={item.href}
            className={`rounded-lg px-4 py-2 text-sm font-medium ${
              pathname.startsWith(item.href)
                ? "bg-amber-600 text-white"
                : "bg-stone-100 text-stone-700 hover:bg-stone-200"
            }`}
          >
            {item.label}
          </Link>
        ))}
      </nav>
      {children}
    </div>
  );
}
