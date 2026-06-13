import Link from "next/link";
import type { BookSummary } from "@/types/catalog";
import { formatPrice } from "@/lib/formatPrice";

interface BookCardProps {
  book: BookSummary;
}

/**
 * Carte livre pour le catalogue.
 */
export function BookCard({ book }: BookCardProps) {
  const lowestPrice = book.formats
    ?.map((format) => format.currentPrice?.price)
    .filter(Boolean)
    .map((price) => parseFloat(price as string))
    .sort((a, b) => a - b)[0];

  return (
    <article className="flex flex-col overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm transition hover:shadow-md">
      <div className="aspect-[3/4] bg-stone-100">
        {book.coverImage ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img
            src={book.coverImage}
            alt={book.title}
            className="h-full w-full object-cover"
          />
        ) : (
          <div className="flex h-full items-center justify-center text-stone-400">
            Pas de couverture
          </div>
        )}
      </div>
      <div className="flex flex-1 flex-col p-5">
        <p className="text-sm text-amber-700">{book.author?.fullName}</p>
        <h2 className="mt-1 text-lg font-semibold text-stone-900">{book.title}</h2>
        <p className="mt-2 line-clamp-2 text-sm text-stone-600">{book.description}</p>
        <div className="mt-auto flex items-center justify-between pt-4">
          <span className="text-sm font-semibold text-stone-900">
            {lowestPrice ? `À partir de ${formatPrice(lowestPrice)}` : "Prix à venir"}
          </span>
          <Link
            href={`/livres/${book.slug}`}
            className="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700"
          >
            Voir
          </Link>
        </div>
      </div>
    </article>
  );
}
