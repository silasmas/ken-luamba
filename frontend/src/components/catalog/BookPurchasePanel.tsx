"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import {
  Bell,
  BookOpen,
  Check,
  Headphones,
  Heart,
  ShoppingBag,
  Smartphone,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { useCartStore } from "@/stores/cartStore";
import { useWishlistStore } from "@/stores/wishlistStore";
import type { BookAvailabilityStatus, BookDetail, BookFormat } from "@/types/catalog";
import { formatPrice } from "@/lib/formatPrice";
import { cn } from "@/lib/utils";

/**
 * Retourne l'icône associée à un format livre.
 *
 * @param format Format API
 * @returns Composant icône Lucide
 */
function formatIcon(format: BookFormat) {
  if (format.type === "audiobook") {
    return Headphones;
  }

  if (format.isDigital) {
    return Smartphone;
  }

  return BookOpen;
}

/**
 * Détail affiché sous le libellé du format.
 *
 * @param format Format API
 * @param pageCount Nombre de pages du livre
 * @returns Texte descriptif du format
 */
function formatDetail(format: BookFormat, pageCount?: number): string {
  if (format.isDigital && format.digitalFileTypeLabel) {
    return `Fichier ${format.digitalFileTypeLabel}`;
  }

  if (pageCount) {
    return `${format.typeLabel} · ${pageCount} pages`;
  }

  return format.typeLabel;
}

/**
 * Panneau d'achat : sélection format + précommande / panier / liste de souhaits.
 */
export function BookPurchasePanel({
  book,
  formats,
  availabilityStatus = "available",
}: {
  book: BookDetail;
  formats: BookFormat[];
  availabilityStatus?: BookAvailabilityStatus;
}) {
  const router = useRouter();
  const addItem = useCartStore((state) => state.addItem);
  const isMutating = useCartStore((state) => state.isMutating);
  const cartError = useCartStore((state) => state.error);
  const itemCount = useCartStore((state) => state.cart?.summary.itemCount ?? 0);
  const toggleWishlist = useWishlistStore((state) => state.toggleBook);
  const isInWishlist = useWishlistStore((state) => state.isInWishlist);
  const initWishlist = useWishlistStore((state) => state.initWishlist);

  const [selectedFormatId, setSelectedFormatId] = useState(
    formats.find((format) => format.currentPrice)?.id ?? formats[0]?.id,
  );
  const [message, setMessage] = useState<string | null>(null);
  const [wishlistMessage, setWishlistMessage] = useState<string | null>(null);

  const selectedFormat = formats.find((format) => format.id === selectedFormatId);
  const isComing = availabilityStatus === "coming";
  const isPreorder = availabilityStatus === "preorder";
  const inWishlist = isInWishlist(book.slug);

  useEffect(() => {
    initWishlist();
  }, [initWishlist]);

  /**
   * Ajoute le format sélectionné au panier.
   */
  const handleAddToCart = async () => {
    if (!selectedFormatId || !selectedFormat?.currentPrice) {
      setMessage("Ce format n'est pas encore disponible à l'achat.");
      return;
    }

    setMessage(null);

    try {
      await addItem(selectedFormatId);
      setMessage("Ajouté au panier !");
    } catch (error) {
      setMessage(
        error instanceof Error ? error.message : "Impossible d'ajouter au panier.",
      );
    }
  };

  /**
   * Ajoute ou retire le livre de la liste de souhaits locale.
   */
  const handleWishlist = () => {
    const added = toggleWishlist(book.slug);
    setWishlistMessage(
      added ? "Ajouté à votre liste." : "Retiré de votre liste.",
    );
  };

  /**
   * Ouvre le panier après un ajout réussi.
   */
  const handleViewCart = () => {
    router.push("/panier");
  };

  return (
    <div
      id="precommande"
      className="rounded-3xl border border-ink/[0.08] bg-white/60 p-6 shadow-[0_30px_60px_-40px_rgba(10,10,10,0.4)] backdrop-blur-sm sm:p-8"
    >
      <p className="eyebrow mb-5">Choisir un format</p>

      <div className="space-y-2.5">
        {formats.map((format) => {
          const Icon = formatIcon(format);
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
                "flex w-full items-center gap-4 rounded-2xl border px-4 py-3.5 text-left transition-all duration-300",
                isSelected ? "border-ink/80 bg-ink/[0.03]" : "border-ink/10 hover:border-ink/30",
                !isAvailable && "cursor-not-allowed opacity-45 hover:border-ink/10",
              )}
            >
              <span
                className={cn(
                  "inline-flex h-10 w-10 items-center justify-center rounded-full transition-colors",
                  isSelected ? "bg-ink text-paper" : "bg-ink/[0.05] text-ink/60",
                )}
              >
                <Icon className="h-4 w-4" />
              </span>
              <span className="flex-1">
                <span className="block text-sm font-medium text-ink">{format.typeLabel}</span>
                <span className="block text-xs text-ink/50">
                  {formatDetail(format, book.pageCount)}
                </span>
              </span>
              <span className="text-right">
                <span className="block font-display text-lg text-ink">
                  {format.currentPrice
                    ? formatPrice(format.currentPrice.price, format.currentPrice.currency)
                    : "—"}
                </span>
                {isSelected && isAvailable && (
                  <Check className="ml-auto h-4 w-4 text-accent" />
                )}
              </span>
            </button>
          );
        })}
      </div>

      {selectedFormat?.isDigital && selectedFormat.digitalLimits && (
        <div className="mt-4 rounded-xl border border-accent/20 bg-accent-soft/30 p-4 text-xs text-ink/70">
          <p className="font-semibold text-ink">Accès numérique personnel</p>
          <p className="mt-1">{selectedFormat.digitalLimits.summary}</p>
        </div>
      )}

      {selectedFormat?.currentPrice && (
        <div className="mt-6 flex items-baseline justify-between border-t border-ink/[0.08] pt-5">
          <span className="text-sm text-ink/55">Total</span>
          <span className="font-display text-2xl text-ink">
            {formatPrice(
              selectedFormat.currentPrice.price,
              selectedFormat.currentPrice.currency,
            )}
          </span>
        </div>
      )}

      <div className="mt-5 space-y-2.5">
        {isComing ? (
          <Button variant="outline" size="lg" className="w-full" disabled>
            <Bell className="h-4 w-4" />
            Bientôt disponible
          </Button>
        ) : (
          <Button
            type="button"
            variant={isPreorder ? "accent" : "primary"}
            size="lg"
            className="w-full"
            disabled={isMutating || !selectedFormat?.currentPrice}
            onClick={() => void handleAddToCart()}
          >
            <ShoppingBag className="h-4 w-4" />
            {isMutating ? "Ajout..." : isPreorder ? "Précommander" : "Ajouter au panier"}
          </Button>
        )}

        {isComing ? (
          <Button
            type="button"
            variant="ghost"
            size="md"
            className="w-full"
            onClick={handleWishlist}
          >
            <Bell className="h-4 w-4" />
            Être prévenu de la sortie
          </Button>
        ) : (
          <Button
            type="button"
            variant="ghost"
            size="md"
            className="w-full"
            onClick={handleWishlist}
          >
            <Heart className={cn("h-4 w-4", inWishlist && "fill-accent text-accent")} />
            {inWishlist ? "Retirer de ma liste" : "Ajouter à ma liste"}
          </Button>
        )}

        <Button
          type="button"
          variant="outline"
          size="md"
          className="w-full"
          onClick={handleViewCart}
        >
          Voir mon panier{itemCount > 0 ? ` (${itemCount})` : ""}
        </Button>
      </div>

      {message && (
        <p
          className={cn(
            "mt-3 text-center text-sm",
            message.includes("Ajouté") ? "text-green-700" : "text-red-600",
          )}
        >
          {message}
          {message.includes("Ajouté") && (
            <>
              {" "}
              <Link href="/panier" className="text-accent underline">
                Ouvrir le panier
              </Link>
            </>
          )}
        </p>
      )}

      {wishlistMessage && (
        <p className="mt-2 text-center text-sm text-ink/60">{wishlistMessage}</p>
      )}

      {cartError && !message && (
        <p className="mt-3 text-center text-sm text-red-600">{cartError}</p>
      )}

      <p className="mt-5 text-center text-xs text-ink/45">
        Paiement sécurisé · Téléchargement immédiat des formats numériques
      </p>
    </div>
  );
}
