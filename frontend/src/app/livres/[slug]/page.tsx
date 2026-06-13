import Link from "next/link";
import { notFound } from "next/navigation";
import { AddToCartButton } from "@/components/shop/AddToCartButton";
import { fetchBook } from "@/lib/api/books";
import { formatPrice } from "@/lib/formatPrice";

export const dynamic = "force-dynamic";

interface BookDetailPageProps {
  params: Promise<{ slug: string }>;
}

/**
 * Page détail d'un livre avec ajout au panier.
 */
export default async function BookDetailPage({ params }: BookDetailPageProps) {
  const { slug } = await params;

  let book;

  try {
    const response = await fetchBook(slug);
    book = response.data;
  } catch {
    notFound();
  }

  const availableFormats = book.formats?.filter((format) => format.currentPrice) ?? [];

  return (
    <div className="grid gap-10 lg:grid-cols-[1fr_380px]">
      <section>
        <Link href="/livres" className="text-sm text-amber-700 hover:underline">
          ← Retour au catalogue
        </Link>
        {book.coverImage && (
          <div className="mt-6 aspect-[3/4] max-w-xs overflow-hidden rounded-xl border border-stone-200 bg-stone-100">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img
              src={book.coverImage}
              alt={`Couverture de ${book.title}`}
              className="h-full w-full object-cover"
            />
          </div>
        )}
        <p className="mt-4 text-sm font-medium text-amber-700">
          Par {book.author?.fullName}
        </p>
        <h1 className="mt-2 text-4xl font-bold text-stone-900">{book.title}</h1>
        <p className="mt-6 leading-relaxed text-stone-600">{book.description}</p>
        {book.authorNote && (
          <blockquote className="mt-8 border-l-4 border-amber-600 pl-4 italic text-stone-700">
            {book.authorNote}
            <footer className="mt-2 not-italic text-sm text-stone-500">
              — {book.author?.fullName}
            </footer>
          </blockquote>
        )}
        <div className="mt-8">
          <Link
            href={`/auteur`}
            className="text-sm font-medium text-amber-700 hover:underline"
          >
            En savoir plus sur l&apos;auteur
          </Link>
        </div>
      </section>
      <aside>
        {availableFormats.length > 0 ? (
          <AddToCartButton formats={book.formats ?? []} />
        ) : (
          <div className="rounded-xl border border-stone-200 bg-white p-6 text-stone-600">
            Ce livre n&apos;est pas disponible à la vente pour le moment.
          </div>
        )}
        <div className="mt-4 rounded-xl border border-stone-200 bg-stone-50 p-4 text-sm text-stone-600">
          {availableFormats.map((format) => (
            <p key={format.id}>
              {format.typeLabel} :{" "}
              {format.currentPrice
                ? formatPrice(format.currentPrice.price, format.currentPrice.currency)
                : "Indisponible"}
            </p>
          ))}
        </div>
      </aside>
    </div>
  );
}
