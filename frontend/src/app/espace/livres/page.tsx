"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { EspaceLayout } from "@/components/espace/EspaceLayout";
import { apiClient } from "@/lib/api/client";
import { useAuthStore } from "@/stores/authStore";

interface LibraryItem {
  id: string;
  bookTitle: string;
  formatType: string;
  formatLabel: string;
  digitalFileTypeLabel?: string | null;
  orderNumber?: string;
  hasFile: boolean;
  downloadCount?: number;
  maxDownloads?: number;
  remainingDownloads?: number;
  streamExpiryHours?: number;
}

/**
 * Page bibliothèque numérique (ebook / audio) avec téléchargement sécurisé.
 */
export default function MesLivresPage() {
  const token = useAuthStore((state) => state.token);
  const isReady = useAuthStore((state) => state.isReady);
  const [items, setItems] = useState<LibraryItem[]>([]);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!token) {
      return;
    }

    void apiClient
      .get<{ data: LibraryItem[] }>("/library", { token })
      .then((response) => setItems(response.data))
      .catch((err) => {
        setError(err instanceof Error ? err.message : "Impossible de charger la bibliothèque.");
      });
  }, [token]);

  /**
   * Ouvre le contenu numérique via URL signée temporaire.
   *
   * @param accessId Identifiant d'accès
   */
  const handleOpen = async (accessId: string) => {
    if (!token) {
      return;
    }

    const response = await apiClient.get<{
      data: { streamUrl: string; bookTitle: string };
    }>(`/library/${accessId}/stream`, { token });

    window.open(response.data.streamUrl, "_blank");
  };

  if (!isReady) {
    return (
      <EspaceLayout>
        <p className="py-10 text-center text-stone-600">Chargement...</p>
      </EspaceLayout>
    );
  }

  if (!token) {
    return (
      <EspaceLayout>
        <div className="py-10 text-center">
          <p className="text-stone-600">Connectez-vous pour accéder à vos livres numériques.</p>
          <Link href="/connexion?redirect=/espace/livres" className="mt-4 inline-block text-amber-700 hover:underline">
            Se connecter
          </Link>
        </div>
      </EspaceLayout>
    );
  }

  return (
    <EspaceLayout>
      <div className="space-y-6">
        <div>
          <h1 className="text-2xl font-bold text-stone-900">Ma bibliothèque numérique</h1>
          <p className="mt-1 text-sm text-stone-600">
            Vos ebooks et livres audio achetés. L&apos;accès est personnel et lié à votre compte.
          </p>
        </div>

        {error && <p className="text-sm text-red-600">{error}</p>}

        {items.length === 0 ? (
          <p className="text-stone-600">Aucun contenu numérique pour le moment.</p>
        ) : (
          items.map((item) => (
            <article
              key={item.id}
              className="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-stone-200 bg-white p-5"
            >
              <div>
                <p className="font-semibold">{item.bookTitle}</p>
                <p className="text-sm text-stone-500">{item.formatLabel}</p>
                {item.digitalFileTypeLabel && (
                  <p className="text-xs text-amber-700">Format : {item.digitalFileTypeLabel}</p>
                )}
                {item.orderNumber && (
                  <p className="text-xs text-stone-400">Commande {item.orderNumber}</p>
                )}
                {item.hasFile && item.maxDownloads !== undefined && (
                  <p className="mt-1 text-xs text-stone-500">
                    {item.remainingDownloads ?? 0} ouverture(s) restante(s) sur {item.maxDownloads}
                    {item.streamExpiryHours ? ` — lien valide ${item.streamExpiryHours} h` : ""}
                  </p>
                )}
              </div>
              <button
                type="button"
                disabled={!item.hasFile || (item.remainingDownloads ?? 1) <= 0}
                onClick={() => void handleOpen(item.id)}
                className="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
              >
                {!item.hasFile
                  ? "Fichier en préparation"
                  : (item.remainingDownloads ?? 1) <= 0
                    ? "Limite atteinte"
                    : item.formatType === "audiobook"
                      ? "Écouter / Télécharger"
                      : "Lire / Télécharger"}
              </button>
            </article>
          ))
        )}
      </div>
    </EspaceLayout>
  );
}
