"use client";

import Image from "next/image";
import Link from "next/link";
import { useEffect, useRef, useState } from "react";
import { BookOpen, ChevronDown, LogOut, Package, Truck, User } from "lucide-react";
import { cn } from "@/lib/utils";
import { resolveMediaUrl } from "@/lib/resolveMediaUrl";
import { getUserInitials } from "@/lib/userDisplay";
import type { User as AuthUser } from "@/types/auth";

interface MenuItem {
  href: string;
  label: string;
  icon: typeof Package;
}

/**
 * Construit les entrées du menu compte selon le rôle utilisateur.
 *
 * @param role Rôle applicatif
 * @returns Liens du menu déroulant
 */
function buildMenuItems(role: string): MenuItem[] {
  const items: MenuItem[] = [
    { href: "/espace/commandes", label: "Mes commandes", icon: Package },
    { href: "/espace/livres", label: "Ma bibliothèque", icon: BookOpen },
    { href: "/espace/profil", label: "Mon profil", icon: User },
  ];

  if (role === "courier") {
    items.push({ href: "/livreur", label: "Espace livreur", icon: Truck });
  }

  return items;
}

/**
 * Menu déroulant du compte connecté (avatar ou initiales).
 */
export function UserAccountMenu({
  user,
  onLogout,
}: {
  user: AuthUser;
  onLogout: () => void | Promise<void>;
}) {
  const [open, setOpen] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);
  const avatarUrl = resolveMediaUrl(user.avatarUrl);
  const initials = getUserInitials(user.fullName);
  const menuItems = buildMenuItems(user.role);

  useEffect(() => {
    /**
     * Ferme le menu au clic extérieur.
     *
     * @param event Événement souris
     */
    const handleClickOutside = (event: MouseEvent) => {
      if (!containerRef.current?.contains(event.target as Node)) {
        setOpen(false);
      }
    };

    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  return (
    <div ref={containerRef} className="relative">
      <button
        type="button"
        aria-expanded={open}
        aria-haspopup="menu"
        onClick={() => setOpen((value) => !value)}
        className="inline-flex items-center gap-2 rounded-full border border-ink/10 bg-white/70 py-1 pl-1 pr-3 text-sm font-medium text-ink transition-colors hover:border-ink/25 hover:bg-white"
      >
        <span className="relative flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-ink text-paper">
          {avatarUrl ? (
            <Image
              src={avatarUrl}
              alt={`Photo de ${user.fullName}`}
              fill
              sizes="36px"
              className="object-cover"
            />
          ) : (
            <span className="text-xs font-semibold tracking-wide">{initials}</span>
          )}
        </span>
        <ChevronDown className={cn("h-4 w-4 text-ink/50 transition-transform", open && "rotate-180")} />
      </button>

      {open && (
        <div
          role="menu"
          className="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-2xl border border-ink/10 bg-paper shadow-[0_20px_50px_-24px_rgba(10,10,10,0.45)]"
        >
          <div className="border-b border-ink/8 px-4 py-3">
            <p className="truncate text-sm font-medium text-ink">{user.fullName}</p>
            <p className="truncate text-xs text-ink/50">{user.email}</p>
          </div>

          <nav className="flex flex-col p-2">
            {menuItems.map((item) => {
              const Icon = item.icon;

              return (
                <Link
                  key={item.href}
                  href={item.href}
                  role="menuitem"
                  onClick={() => setOpen(false)}
                  className="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-ink/80 transition-colors hover:bg-ink/[0.04] hover:text-ink"
                >
                  <Icon className="h-4 w-4 text-ink/45" />
                  {item.label}
                </Link>
              );
            })}
          </nav>

          <div className="border-t border-ink/8 p-2">
            <button
              type="button"
              role="menuitem"
              onClick={() => {
                setOpen(false);
                void onLogout();
              }}
              className="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm text-ink/70 transition-colors hover:bg-ink/[0.04] hover:text-ink"
            >
              <LogOut className="h-4 w-4" />
              Déconnexion
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
