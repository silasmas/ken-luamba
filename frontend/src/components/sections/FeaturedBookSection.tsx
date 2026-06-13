import Link from "next/link";
import { ShoppingBag, BookOpenText, FileText, Clock, Headphones, BookOpen, Smartphone } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Reveal } from "@/components/motion/reveal";
import { CatalogBookCover } from "@/components/catalog/CatalogBookCover";
import type { BookFormat, BookSummary } from "@/types/catalog";

/**
 * Retourne l'icône d'un format pour la section vedette.
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

interface FeaturedBookSectionProps {
  /** Livre vedette depuis l'API */
  book: BookSummary;
}

/**
 * Section dernier ouvrage — alignée sur la maquette book-site.
 */
export function FeaturedBookSection({ book }: FeaturedBookSectionProps) {
  const isPreorder = book.availabilityStatus === "preorder";
  const activeFormats = book.formats?.filter((format) => format.currentPrice) ?? [];

  return (
    <section className="relative overflow-hidden bg-paper py-24 lg:py-32">
      <div className="mx-auto max-w-7xl px-6 lg:px-10">
        <div className="grid items-center gap-14 lg:grid-cols-[0.85fr_1.15fr] lg:gap-20">
          <Reveal className="order-2 flex justify-center lg:order-1">
            <div className="relative">
              <span className="absolute -bottom-2 left-1/2 h-6 w-44 -translate-x-1/2 rounded-[50%] bg-ink/25 blur-2xl" />
              <CatalogBookCover book={book} width={300} />
            </div>
          </Reveal>

          <div className="order-1 lg:order-2">
            <Reveal>
              <div className="mb-5 flex items-center gap-3">
                <span className="eyebrow">Dernier ouvrage</span>
                <span className="h-px w-12 bg-ink/15" />
                {book.availabilityLabel && (
                  <Badge variant={isPreorder ? "accent" : "default"}>{book.availabilityLabel}</Badge>
                )}
              </div>
            </Reveal>

            <Reveal delay={1}>
              <h2 className="font-display text-4xl leading-[1.05] tracking-tight text-ink text-balance sm:text-5xl lg:text-[3.4rem]">
                {book.title}
              </h2>
            </Reveal>

            <Reveal delay={2}>
              <p className="mt-5 max-w-xl font-serif-ed text-[1.2rem] leading-relaxed text-ink/65">
                {book.subtitle ?? book.description}
              </p>
            </Reveal>

            <Reveal delay={3}>
              <div className="mt-8 flex flex-wrap items-center gap-x-7 gap-y-3 text-sm text-ink/60">
                {book.pageCount && (
                  <span className="inline-flex items-center gap-2">
                    <FileText className="h-4 w-4 text-ink/40" />
                    {book.pageCount} pages
                  </span>
                )}
                {book.readingTime && (
                  <span className="inline-flex items-center gap-2">
                    <Clock className="h-4 w-4 text-ink/40" />
                    {book.readingTime}
                  </span>
                )}
                {book.language && (
                  <span className="inline-flex items-center gap-2">
                    {book.language}
                  </span>
                )}
                {activeFormats.map((format) => {
                  const Icon = formatIcon(format);
                  return (
                    <span
                      key={format.id}
                      className="inline-flex items-center gap-1.5 rounded-full border border-ink/10 px-3 py-1 text-xs text-ink/65"
                    >
                      <Icon className="h-3.5 w-3.5" />
                      {format.typeLabel}
                    </span>
                  );
                })}
              </div>
            </Reveal>

            <div className="mt-10 flex flex-col gap-3 sm:flex-row">
              <Button asChild variant="accent" size="lg">
                <Link href={`/livres/${book.slug}#precommande`}>
                  <ShoppingBag className="h-4 w-4" />
                  {isPreorder ? "Précommander" : "Acheter"}
                </Link>
              </Button>
              <Button asChild variant="outline" size="lg">
                <Link href={`/livres/${book.slug}#extrait`}>
                  <BookOpenText className="h-4 w-4" />
                  Lire un extrait
                </Link>
              </Button>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
