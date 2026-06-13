import Link from "next/link";
import { ArrowRight } from "lucide-react";
import { BookCard } from "@/components/shop/BookCard";
import { Button } from "@/components/ui/button";
import { Reveal, Stagger, StaggerItem } from "@/components/motion/reveal";
import type { BookSummary } from "@/types/catalog";

interface LibraryHomeSectionProps {
  /** Livres à afficher */
  books: BookSummary[];
}

/**
 * Section bibliothèque sur la page d'accueil.
 */
export function LibraryHomeSection({ books }: LibraryHomeSectionProps) {
  return (
    <section className="relative bg-paper py-24 lg:py-32">
      <div className="mx-auto max-w-7xl px-6 lg:px-10">
        <div className="mb-16 flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-end">
          <div className="max-w-xl">
            <Reveal>
              <span className="eyebrow">La bibliothèque</span>
            </Reveal>
            <Reveal delay={1}>
              <h2 className="mt-4 font-display text-4xl leading-[1.05] tracking-tight text-ink sm:text-5xl">
                Une collection à parcourir, livre après livre.
              </h2>
            </Reveal>
          </div>
          <Reveal delay={2}>
            <Button asChild variant="outline" size="md">
              <Link href="/livres">
                Tout voir
                <ArrowRight className="h-4 w-4" />
              </Link>
            </Button>
          </Reveal>
        </div>

        <Stagger className="grid gap-12 sm:grid-cols-2 lg:grid-cols-3 lg:gap-8">
          {books.map((book) => (
            <StaggerItem key={book.id}>
              <BookCard book={book} variant="editorial" />
            </StaggerItem>
          ))}
        </Stagger>
      </div>
    </section>
  );
}
