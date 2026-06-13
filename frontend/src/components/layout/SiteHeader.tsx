"use client";

import Image from "next/image";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { Menu, User, X } from "lucide-react";
import { cn } from "@/lib/utils";
import { BrandMark } from "@/components/brand/BrandMark";
import { Button } from "@/components/ui/button";
import { HeaderCartLink } from "@/components/cart/HeaderCartLink";
import { UserAccountMenu } from "@/components/layout/UserAccountMenu";
import { resolveMediaUrl } from "@/lib/resolveMediaUrl";
import { getUserInitials } from "@/lib/userDisplay";
import { useAuthStore } from "@/stores/authStore";

const NAV = [
  { href: "/", label: "Accueil" },
  { href: "/livres", label: "Bibliothèque" },
  { href: "/auteur", label: "L'auteur" },
];

/**
 * En-tête premium conservant toute la navigation et l'auth existantes.
 */
export function SiteHeader() {
  const pathname = usePathname();
  const router = useRouter();
  const user = useAuthStore((state) => state.user);
  const logout = useAuthStore((state) => state.logout);
  const [scrolled, setScrolled] = useState(false);
  const [open, setOpen] = useState(false);

  useEffect(() => {
    const onScroll = () => {
      setScrolled(window.scrollY > 16);
    };
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  useEffect(() => {
    setOpen(false);
  }, [pathname]);

  /**
   * Déconnecte l'utilisateur.
   */
  const handleLogout = async () => {
    await logout();
    router.refresh();
  };

  return (
    <header
      className={cn(
        "sticky top-0 z-50 transition-all duration-500",
        scrolled
          ? "border-b border-ink/[0.08] bg-paper/85 backdrop-blur-xl"
          : "border-b border-transparent bg-paper/40 backdrop-blur-sm",
      )}
    >
      <div className="mx-auto flex h-[4.5rem] max-w-7xl items-center justify-between gap-3 px-6 lg:px-10">
        <BrandMark size="lg" />

        <nav className="hidden items-center gap-1 md:flex">
          {NAV.map((item) => {
            const active =
              item.href === "/"
                ? pathname === "/"
                : pathname.startsWith(item.href);

            return (
              <Link
                key={item.href}
                href={item.href}
                className={cn(
                  "rounded-full px-4 py-2 text-[0.9rem] font-medium transition-colors",
                  active ? "text-ink" : "text-ink/55 hover:text-ink",
                )}
              >
                {item.label}
              </Link>
            );
          })}
        </nav>

        <div className="flex items-center gap-2">
          <HeaderCartLink className="shrink-0" />

          <div className="hidden items-center gap-2 md:flex">
            {!user && (
              <Button asChild variant="ghost" size="sm">
                <Link href="/connexion">
                  <User className="h-4 w-4" />
                  Connexion
                </Link>
              </Button>
            )}

            <Button asChild variant="primary" size="sm">
              <Link href="/livres">Découvrir</Link>
            </Button>

            {user && <UserAccountMenu user={user} onLogout={handleLogout} />}
          </div>

          <button
            type="button"
            className="inline-flex h-11 w-11 items-center justify-center rounded-full text-ink md:hidden"
            onClick={() => setOpen((value) => !value)}
            aria-label="Menu"
          >
            {open ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
          </button>
        </div>
      </div>

      <div
        className={cn(
          "overflow-hidden border-t border-ink/[0.06] bg-paper/95 backdrop-blur-xl transition-all md:hidden",
          open ? "max-h-[36rem]" : "max-h-0",
        )}
      >
        <nav className="flex flex-col gap-0.5 px-6 py-5">
          {NAV.map((item) => (
            <Link
              key={item.href}
              href={item.href}
              className="rounded-xl px-3 py-3.5 text-[0.95rem] font-medium text-ink/70 hover:bg-ink/[0.03]"
            >
              {item.label}
            </Link>
          ))}
          {user ? (
            <>
              <div className="my-2 flex items-center gap-3 border-t border-ink/8 px-3 pt-4">
                <span className="relative flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-ink text-paper">
                  {resolveMediaUrl(user.avatarUrl) ? (
                    <Image
                      src={resolveMediaUrl(user.avatarUrl)!}
                      alt={`Photo de ${user.fullName}`}
                      fill
                      sizes="40px"
                      className="object-cover"
                    />
                  ) : (
                    <span className="text-xs font-semibold tracking-wide">
                      {getUserInitials(user.fullName)}
                    </span>
                  )}
                </span>
                <div className="min-w-0">
                  <p className="truncate text-sm font-medium text-ink">{user.fullName}</p>
                  <p className="truncate text-xs text-ink/50">{user.email}</p>
                </div>
              </div>
              <Link href="/espace/commandes" className="rounded-xl px-3 py-3.5 text-sm">
                Mes commandes
              </Link>
              <Link href="/espace/livres" className="rounded-xl px-3 py-3.5 text-sm">
                Ma bibliothèque
              </Link>
              <Link href="/espace/profil" className="rounded-xl px-3 py-3.5 text-sm">
                Mon profil
              </Link>
              {user.role === "courier" && (
                <Link href="/livreur" className="rounded-xl px-3 py-3.5 text-sm">
                  Espace livreur
                </Link>
              )}
              <button
                type="button"
                onClick={() => void handleLogout()}
                className="rounded-xl px-3 py-3.5 text-left text-sm text-ink/70"
              >
                Déconnexion
              </button>
            </>
          ) : (
            <Button asChild variant="outline" size="md" className="mt-2">
              <Link href="/connexion">Connexion</Link>
            </Button>
          )}
        </nav>
      </div>
    </header>
  );
}
