import Link from "next/link";
import { BrandMark } from "@/components/brand/BrandMark";

const COLUMNS = [
  {
    title: "Boutique",
    links: [
      { label: "Bibliothèque", href: "/livres" },
      { label: "Panier", href: "/panier" },
      { label: "Connexion", href: "/connexion" },
    ],
  },
  {
    title: "Découvrir",
    links: [
      { label: "Accueil", href: "/" },
      { label: "L'auteur", href: "/auteur" },
      { label: "Mes commandes", href: "/espace/commandes" },
    ],
  },
  {
    title: "Formats",
    links: [
      { label: "Livre relié", href: "/livres" },
      { label: "Ebook", href: "/livres" },
      { label: "Livre audio", href: "/livres" },
    ],
  },
];

/**
 * Pied de page éditorial du site.
 */
export function SiteFooter() {
  return (
    <footer className="relative mt-24 border-t border-ink/[0.08] bg-paper">
      <div className="mx-auto max-w-7xl px-6 py-16 lg:px-10">
        <div className="grid gap-12 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
          <div className="max-w-xs">
            <BrandMark />
            <p className="mt-5 font-serif-ed text-[1.05rem] leading-relaxed text-ink/60">
              Des ouvrages qui préparent une génération aux défis de son temps.
            </p>
          </div>

          {COLUMNS.map((col) => (
            <div key={col.title}>
              <h4 className="eyebrow mb-4">{col.title}</h4>
              <ul className="space-y-3">
                {col.links.map((link) => (
                  <li key={link.label}>
                    <Link
                      href={link.href}
                      className="text-sm text-ink/60 transition-colors hover:text-ink"
                    >
                      {link.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>

        <div className="mt-14 flex flex-col items-start justify-between gap-4 border-t border-ink/[0.08] pt-8 text-xs text-ink/45 sm:flex-row sm:items-center">
          <p>© {new Date().getFullYear()} Ken Luamba — Éditions Philadelphie. Tous droits réservés.</p>
        </div>
      </div>
    </footer>
  );
}
