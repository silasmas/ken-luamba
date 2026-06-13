import Link from "next/link";
import { BookCard } from "@/components/shop/BookCard";
import { apiClient } from "@/lib/api/client";
import type { AuthorDetail } from "@/types/catalog";

export const dynamic = "force-dynamic";

/**
 * Page dédiée au pasteur Ken Luamba.
 */
export default async function AuteurPage() {
  const response = await apiClient.get<{ data: AuthorDetail }>("/authors/ken-luamba");
  const author = response.data;

  return (
    <div>
      <section className="rounded-2xl bg-stone-900 px-8 py-12 text-white">
        <p className="text-sm uppercase tracking-widest text-amber-400">L&apos;auteur</p>
        <h1 className="mt-3 text-4xl font-bold">{author.fullName}</h1>
        <p className="mt-2 text-lg text-stone-300">{author.title}</p>
        {author.featuredQuote && (
          <blockquote className="mt-6 max-w-2xl border-l-4 border-amber-500 pl-4 italic text-stone-200">
            {author.featuredQuote}
          </blockquote>
        )}
      </section>

      <section className="mt-10 grid gap-8 lg:grid-cols-[2fr_1fr]">
        <div>
          <h2 className="text-2xl font-bold text-stone-900">Biographie</h2>
          <p className="mt-4 leading-relaxed text-stone-600">{author.fullBio}</p>
        </div>
        <aside className="rounded-xl border border-stone-200 bg-white p-6">
          <h3 className="font-semibold text-stone-900">Réseaux</h3>
          <ul className="mt-4 space-y-2 text-sm">
            {Object.entries(author.socialLinks ?? {}).map(([platform, url]) => (
              <li key={platform}>
                <a href={url} className="text-amber-700 hover:underline" target="_blank" rel="noreferrer">
                  {platform}
                </a>
              </li>
            ))}
          </ul>
        </aside>
      </section>

      {author.books && author.books.length > 0 && (
        <section className="mt-12">
          <h2 className="text-2xl font-bold text-stone-900">Ses ouvrages</h2>
          <div className="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {author.books.map((book) => (
              <BookCard key={book.id} book={book} />
            ))}
          </div>
        </section>
      )}

      <div className="mt-10">
        <Link href="/livres" className="text-amber-700 hover:underline">
          Voir tout le catalogue →
        </Link>
      </div>
    </div>
  );
}
