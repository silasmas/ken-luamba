"use client";

import { useState } from "react";
import { useCartStore } from "@/stores/cartStore";
import type { BookFormat } from "@/types/catalog";
import { formatPrice } from "@/lib/formatPrice";

interface AddToCartButtonProps {
  formats: BookFormat[];
}

/**
 * Affiche les restrictions d'accès pour un format numérique.
 *
 * @param format Format ebook ou audio
 */
function DigitalLimitsNotice({ format }: { format: BookFormat }) {
  if (!format.isDigital || !format.digitalLimits) {
    return null;
  }

  const limits = format.digitalLimits;

  return (
    <div className="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-stone-700">
      <p className="font-semibold text-stone-900">Conditions d&apos;accès numérique</p>
      <ul className="mt-2 list-disc space-y-1 pl-5">
        {limits.fileTypeLabel && (
          <li>Format fourni : <strong>{limits.fileTypeLabel}</strong></li>
        )}
        <li>Accès personnel lié à votre compte uniquement</li>
        <li>Lien de lecture valide {limits.streamExpiryHours} h</li>
        <li>Maximum {limits.maxDownloads} ouvertures / téléchargements</li>
        <li>Partage interdit — usage strictement personnel</li>
      </ul>
      <p className="mt-2 text-xs text-stone-500">{limits.summary}</p>
    </div>
  );
}

/**
 * Sélecteur de format et bouton d'ajout au panier.
 */
export function AddToCartButton({ formats }: AddToCartButtonProps) {
  const addItem = useCartStore((state) => state.addItem);
  const isLoading = useCartStore((state) => state.isLoading);
  const [selectedFormatId, setSelectedFormatId] = useState(
    formats.find((format) => format.currentPrice)?.id ?? formats[0]?.id,
  );
  const [message, setMessage] = useState<string | null>(null);

  const selectedFormat = formats.find((format) => format.id === selectedFormatId);

  /**
   * Ajoute le format sélectionné au panier.
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
    <div className="rounded-xl border border-stone-200 bg-white p-6">
      <h3 className="text-lg font-semibold text-stone-900">Choisir un format</h3>
      <div className="mt-4 space-y-3">
        {formats.map((format) => {
          const isAvailable = Boolean(format.currentPrice);

          return (
          <label
            key={format.id}
            className={`flex items-center justify-between rounded-lg border px-4 py-3 ${
              isAvailable ? "cursor-pointer" : "cursor-not-allowed opacity-60"
            } ${
              selectedFormatId === format.id
                ? "border-amber-600 bg-amber-50"
                : "border-stone-200"
            }`}
          >
            <span className="flex items-center gap-3">
              <input
                type="radio"
                name="format"
                checked={selectedFormatId === format.id}
                disabled={!isAvailable}
                onChange={() => {
                  if (isAvailable) {
                    setSelectedFormatId(format.id);
                  }
                }}
              />
              <span>
                <span className="font-medium text-stone-900">{format.typeLabel}</span>
                <span className="block text-sm text-stone-500">{format.sku}</span>
                {format.isDigital && format.digitalFileTypeLabel && (
                  <span className="block text-xs text-amber-700">
                    Fichier {format.digitalFileTypeLabel}
                  </span>
                )}
              </span>
            </span>
            <span className="font-semibold text-stone-900">
              {format.currentPrice
                ? formatPrice(format.currentPrice.price, format.currentPrice.currency)
                : "Indisponible"}
            </span>
          </label>
          );
        })}
      </div>
      {selectedFormat && <DigitalLimitsNotice format={selectedFormat} />}
      <button
        type="button"
        onClick={handleAddToCart}
        disabled={isLoading || !selectedFormat?.currentPrice}
        className="mt-6 w-full rounded-lg bg-amber-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-50"
      >
        {isLoading ? "Ajout..." : "Ajouter au panier"}
      </button>
      {message && <p className="mt-3 text-sm text-amber-700">{message}</p>}
    </div>
  );
}
