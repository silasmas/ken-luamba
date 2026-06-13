import Link from "next/link";
import { BookCard } from "@/components/shop/BookCard";
import { fetchBooks } from "@/lib/api/books";

export const dynamic = "force-dynamic";

/**
 * Page d'accueil de la boutique Ken Luamba.
 */
export default async function Home() {
  const response = await fetchBooks({ featured: true });
  const featuredBooks = response.data.length > 0
    ? response.data
    : (await fetchBooks()).data.slice(0, 3);

  return (
    <div>
      <section className="max-w-2xl">
        <p className="mb-4 text-sm font-medium uppercase tracking-widest text-amber-700">
          Site officiel
        </p>
        <h1 className="mb-6 text-4xl font-bold leading-tight tracking-tight md:text-5xl">
          Pré-commandez et achetez les ouvrages de Ken Luamba
        </h1>
        <p className="mb-10 text-lg leading-relaxed text-stone-600">
          Formats livre relié, ebook et audio. Livraison ou retrait avec QR code.
          Espace membre sécurisé pour accéder à vos achats.
        </p>
        <div className="flex flex-wrap gap-4">
          <Link
            href="/livres"
            className="rounded-lg bg-amber-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-amber-700"
          >
            Voir les livres
          </Link>
          <Link
            href="/auteur"
            className="rounded-lg border border-stone-300 bg-white px-6 py-3 text-sm font-semibold text-stone-800 transition hover:border-stone-400"
          >
            Découvrir l&apos;auteur
          </Link>
        </div>
      </section>

      <section className="mt-16">
        <div className="mb-6 flex items-center justify-between">
          <h2 className="text-2xl font-bold text-stone-900">À la une</h2>
          <Link href="/livres" className="text-sm font-medium text-amber-700 hover:underline">
            Tout voir
          </Link>
        </div>
        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {featuredBooks.map((book) => (
            <BookCard key={book.id} book={book} />
          ))}
        </div>
      </section>
    </div>
  );
}
