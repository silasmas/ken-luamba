import Link from "next/link";
import { ArrowUpRight } from "lucide-react";
import { CatalogBookCover } from "@/components/catalog/CatalogBookCover";
import { Badge } from "@/components/ui/badge";
import { bookPriceLabel } from "@/lib/catalogUi";
import type { BookSummary } from "@/types/catalog";

interface BookCardProps {
  book: BookSummary;
  /** Variante visuelle */
  variant?: "default" | "editorial";
}

/**
 * Carte livre catalogue — variante classique ou éditoriale.
 */
export function BookCard({ book, variant = "default" }: BookCardProps) {
  if (variant === "editorial") {
    return (
      <Link
        href={`/livres/${book.slug}`}
        className="group relative flex flex-col items-center"
      >
        <div className="relative flex h-[340px] w-full items-end justify-center">
          <span className="absolute bottom-6 h-5 w-28 rounded-[50%] bg-ink/20 blur-xl transition-all duration-700 group-hover:w-32" />
          <div className="transition-transform duration-700 group-hover:-translate-y-3">
            <CatalogBookCover book={book} width={188} />
          </div>
        </div>

        <div className="mt-7 w-full text-center">
          {book.availabilityLabel && (
            <div className="mb-3 flex justify-center">
              <Badge variant={book.availabilityStatus === "preorder" ? "accent" : "default"}>
                {book.availabilityLabel}
              </Badge>
            </div>
          )}
          <h3 className="font-display text-[1.35rem] leading-tight tracking-tight text-ink">
            {book.title}
          </h3>
          <p className="mx-auto mt-2 max-w-xs line-clamp-2 font-serif-ed text-[0.98rem] text-ink/55">
            {book.tagline ?? book.description}
          </p>
          <div className="mt-4 flex items-center justify-center gap-2 text-sm text-ink/70">
            <span>{bookPriceLabel(book)}</span>
            <span className="inline-flex items-center gap-0.5 text-accent opacity-0 transition-all group-hover:opacity-100">
              Découvrir <ArrowUpRight className="h-3.5 w-3.5" />
            </span>
          </div>
        </div>
      </Link>
    );
  }

  return (
    <article className="flex flex-col overflow-hidden rounded-3xl border border-ink/[0.08] bg-white/60 transition hover:shadow-[0_30px_60px_-40px_rgba(10,10,10,0.35)]">
      <div className="flex justify-center bg-paper py-8">
        <CatalogBookCover book={book} width={140} interactive={false} />
      </div>
      <div className="flex flex-1 flex-col p-5">
        <p className="text-sm text-ink/55">{book.author?.fullName}</p>
        <h2 className="mt-1 font-display text-lg text-ink">{book.title}</h2>
        <p className="mt-2 line-clamp-2 text-sm text-ink/60">{book.description}</p>
        <div className="mt-auto flex items-center justify-between pt-4">
          <span className="text-sm font-medium text-ink">{bookPriceLabel(book)}</span>
          <Link
            href={`/livres/${book.slug}`}
            className="rounded-full bg-ink px-4 py-2 text-sm font-medium text-paper"
          >
            Voir
          </Link>
        </div>
      </div>
    </article>
  );
}
