"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { CartBadge } from "@/components/cart/CartBadge";
import { useAuthStore } from "@/stores/authStore";

/**
 * En-tête global du site boutique.
 */
export function Header() {
  const router = useRouter();
  const user = useAuthStore((state) => state.user);
  const logout = useAuthStore((state) => state.logout);

  /**
   * Déconnecte l'utilisateur et rafraîchit la page.
   */
  const handleLogout = async () => {
    await logout();
    router.refresh();
  };

  return (
    <header className="border-b border-stone-200 bg-white">
      <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <Link href="/" className="text-lg font-semibold tracking-tight text-stone-900">
          Ken Luamba
        </Link>
        <nav className="flex items-center gap-6 text-sm font-medium text-stone-600">
          <Link href="/livres" className="hover:text-stone-900">
            Livres
          </Link>
          <Link href="/auteur" className="hover:text-stone-900">
            L&apos;auteur
          </Link>
          <Link href="/panier" className="hover:text-stone-900">
            Panier
            <CartBadge />
          </Link>
          {user ? (
            <>
              <Link href="/espace/commandes" className="hover:text-stone-900">
                Mes commandes
              </Link>
              <Link href="/espace/livres" className="hover:text-stone-900">
                Ma bibliothèque
              </Link>
              <Link href="/espace/profil" className="hover:text-stone-900">
                Mon profil
              </Link>
              {user.role === "courier" && (
                <Link href="/livreur" className="hover:text-stone-900">
                  Espace livreur
                </Link>
              )}
              <span className="text-stone-500">{user.fullName}</span>
              <button type="button" onClick={() => void handleLogout()} className="hover:text-stone-900">
                Déconnexion
              </button>
            </>
          ) : (
            <Link href="/connexion" className="hover:text-stone-900">
              Connexion
            </Link>
          )}
        </nav>
      </div>
    </header>
  );
}
