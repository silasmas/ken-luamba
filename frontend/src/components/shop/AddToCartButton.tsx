"use client";

import { useState } from "react";
import { useCartStore } from "@/stores/cartStore";
import type { BookFormat } from "@/types/catalog";
import { formatPrice } from "@/lib/formatPrice";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

interface AddToCartButtonProps {
  formats: BookFormat[];
}

/**
 * Sélecteur de format et ajout au panier — habillage éditorial.
 */
export function AddToCartButton({ formats }: AddToCartButtonProps) {
  const addItem = useCartStore((state) => state.addItem);
  const isMutating = useCartStore((state) => state.isMutating);
  const [selectedFormatId, setSelectedFormatId] = useState(
    formats.find((format) => format.currentPrice)?.id ?? formats[0]?.id,
  );
  const [message, setMessage] = useState<string | null>(null);

  const selectedFormat = formats.find((format) => format.id === selectedFormatId);

  /**
   * Ajoute le format sélectionné au panier (logique inchangée).
   */
  const handleAddToCart = async () => {
    if (!selectedFormatId || !selectedFormat?.currentPrice) {
      return;
    }

    try {
      await addItem(selectedFormatId);
      setMessage("Ajouté au panier !");
    } catch {
      setMessage("Impossible d'ajouter au panier.");
    }
  };

  return (
    <div className="rounded-3xl border border-ink/[0.08] bg-white/60 p-6 shadow-[0_30px_60px_-40px_rgba(10,10,10,0.4)] backdrop-blur-sm sm:p-8">
      <p className="eyebrow mb-5">Choisir un format</p>

      <div className="space-y-2.5">
        {formats.map((format) => {
          const isAvailable = Boolean(format.currentPrice);
          const isSelected = selectedFormatId === format.id;

          return (
            <button
              key={format.id}
              type="button"
              disabled={!isAvailable}
              onClick={() => {
                if (isAvailable) {
                  setSelectedFormatId(format.id);
                }
              }}
              className={cn(
                "flex w-full items-center gap-4 rounded-2xl border px-4 py-3.5 text-left transition-all",
                isSelected ? "border-ink/80 bg-ink/[0.03]" : "border-ink/10 hover:border-ink/30",
                !isAvailable && "cursor-not-allowed opacity-45",
              )}
            >
              <span className="flex-1">
                <span className="block text-sm font-medium text-ink">{format.typeLabel}</span>
                <span className="block text-xs text-ink/50">{format.sku}</span>
                {format.isDigital && format.digitalFileTypeLabel && (
                  <span className="block text-xs text-accent">Fichier {format.digitalFileTypeLabel}</span>
                )}
              </span>
              <span className="font-display text-lg text-ink">
                {format.currentPrice
                  ? formatPrice(format.currentPrice.price, format.currentPrice.currency)
                  : "—"}
              </span>
            </button>
          );
        })}
      </div>

      {selectedFormat?.isDigital && selectedFormat.digitalLimits && (
        <div className="mt-4 rounded-xl border border-accent/20 bg-accent-soft/30 p-4 text-xs text-ink/70">
          <p className="font-semibold text-ink">Accès numérique personnel</p>
          <p className="mt-1">
            Lien {selectedFormat.digitalLimits.streamExpiryHours} h · max{" "}
            {selectedFormat.digitalLimits.maxDownloads} ouvertures
          </p>
        </div>
      )}

      <Button
        type="button"
        variant="primary"
        size="lg"
        className="mt-6 w-full"
        disabled={isMutating || !selectedFormat?.currentPrice}
        onClick={() => void handleAddToCart()}
      >
        {isMutating ? "Ajout..." : "Ajouter au panier"}
      </Button>

      {message && <p className="mt-3 text-center text-sm text-accent">{message}</p>}
    </div>
  );
}