import { BookCard } from "@/components/shop/BookCard";
import { fetchBooks } from "@/lib/api/books";
import { Reveal } from "@/components/motion/reveal";

export const dynamic = "force-dynamic";

/**
 * Page catalogue des livres — habillage éditorial.
 */
export default async function LivresPage() {
  const response = await fetchBooks();

  return (
    <div className="bg-paper pb-24">
      <section className="border-b border-ink/[0.06] py-16 lg:py-20">
        <div className="mx-auto max-w-7xl px-6 lg:px-10">
          <Reveal>
            <span className="eyebrow">Bibliothèque</span>
          </Reveal>
          <Reveal delay={1}>
            <h1 className="mt-4 font-display text-4xl tracking-tight text-ink sm:text-5xl">
              Les ouvrages
            </h1>
          </Reveal>
          <Reveal delay={2}>
            <p className="mt-4 max-w-2xl text-lg text-ink/60">
              Pré-commandez et achetez les livres du pasteur Ken Luamba. Formats relié,
              ebook et audio disponibles selon les titres.
            </p>
          </Reveal>
        </div>
      </section>

      <div className="mx-auto max-w-7xl px-6 pt-16 lg:px-10">
        <div className="grid gap-12 sm:grid-cols-2 lg:grid-cols-3 lg:gap-8">
          {response.data.map((book) => (
            <BookCard key={book.id} book={book} variant="editorial" />
          ))}
        </div>
      </div>
    </div>
  );
}
