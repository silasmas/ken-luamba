import Link from "next/link";
import { notFound } from "next/navigation";
import { ArrowLeft, Star } from "lucide-react";
import { BookExcerptReader } from "@/components/catalog/BookExcerptReader";
import { BookPurchasePanel } from "@/components/catalog/BookPurchasePanel";
import { BookReviewSection } from "@/components/catalog/BookReviewSection";
import { CatalogBookCover } from "@/components/catalog/CatalogBookCover";
import { ReleaseCountdown } from "@/components/catalog/ReleaseCountdown";
import { Badge } from "@/components/ui/badge";
import { Reveal, Stagger, StaggerItem } from "@/components/motion/reveal";
import { fetchBook } from "@/lib/api/books";
import type { BookDetail, BookSummary } from "@/types/catalog";

export const dynamic = "force-dynamic";

const dateFormatter = new Intl.DateTimeFormat("fr-FR", {
  day: "numeric",
  month: "long",
  year: "numeric",
});

interface BookDetailPageProps {
  params: Promise<{ slug: string }>;
}

/**
 * Page détail livre — design maquette book-site + données API dynamiques.
 */
export default async function BookDetailPage({ params }: BookDetailPageProps) {
  const { slug } = await params;

  let book: BookDetail;

  try {
    const response = await fetchBook(slug);
    book = response.data;
  } catch {
    notFound();
  }

  const formats = book.formats ?? [];
  const purchasableFormats = formats.filter((format) => format.currentPrice);
  const averageRating = book.reviewStats?.averageRating ?? null;
  const reviewCount = book.reviewStats?.count ?? 0;
  const aboutParagraphs =
    book.aboutParagraphs && book.aboutParagraphs.length > 0
      ? book.aboutParagraphs
      : book.description
        ? [book.description]
        : [];
  const accent = book.accentColor ?? "#1b1f2a";

  return (
    <div className="bg-paper">
      <section className="paper-grain relative overflow-hidden border-b border-ink/[0.06] pb-20 pt-10 lg:pt-16">
        <div
          className="pointer-events-none absolute inset-0 opacity-[0.5]"
          style={{
            background: `radial-gradient(80% 60% at 70% 0%, ${accent}14 0%, transparent 60%)`,
          }}
        />
        <div className="relative mx-auto max-w-7xl px-6 lg:px-10">
          <Link
            href="/livres"
            className="mb-10 inline-flex items-center gap-2 text-sm text-ink/55 transition-colors hover:text-ink"
          >
            <ArrowLeft className="h-4 w-4" />
            Bibliothèque
          </Link>

          <div className="flex flex-col gap-8 lg:grid lg:grid-cols-[0.9fr_1.1fr] lg:gap-16">
            <section className="order-1 space-y-4 lg:order-2 lg:col-start-2 lg:pt-4">
              <div className="flex flex-wrap items-center gap-3">
                {book.availabilityLabel && (
                  <Badge
                    variant={book.availabilityStatus === "preorder" ? "accent" : "default"}
                  >
                    {book.availabilityLabel}
                  </Badge>
                )}
                {book.category && (
                  <span className="text-sm text-ink/45">{book.category}</span>
                )}
              </div>

              <h1 className="font-display text-5xl leading-[1.02] tracking-tight text-ink text-balance lg:text-[4rem]">
                {book.title}
              </h1>

              {book.subtitle && (
                <p className="max-w-xl font-serif-ed text-[1.3rem] leading-relaxed text-ink/65">
                  {book.subtitle}
                </p>
              )}
            </section>

            {formats.length > 0 && (
              <section className="order-2 lg:order-1 lg:col-start-1 lg:row-span-2">
                <div className="space-y-10 lg:sticky lg:top-28">
                  <div className="hidden justify-center lg:flex">
                    <div className="relative">
                      <span className="absolute -bottom-3 left-1/2 h-6 w-44 -translate-x-1/2 rounded-[50%] bg-ink/25 blur-2xl" />
                      <CatalogBookCover book={book} width={280} />
                    </div>
                  </div>
                  <BookPurchasePanel
                    book={book}
                    formats={formats}
                    availabilityStatus={book.availabilityStatus}
                  />
                </div>
              </section>
            )}

            <section className="order-3 space-y-6 lg:order-2 lg:col-start-2">
              {averageRating !== null && reviewCount > 0 && (
                <div className="flex items-center gap-4">
                  <div className="flex items-center gap-0.5">
                    {Array.from({ length: 5 }).map((_, index) => (
                      <Star
                        key={index}
                        className={
                          index < Math.round(averageRating)
                            ? "h-4 w-4 fill-accent text-accent"
                            : "h-4 w-4 text-ink/20"
                        }
                      />
                    ))}
                  </div>
                  <span className="text-sm text-ink/55">
                    {averageRating.toFixed(1)} · {reviewCount} avis
                  </span>
                </div>
              )}

              <div className="grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-ink/[0.08] bg-ink/[0.06] sm:grid-cols-4">
                {[
                  { label: "Pages", value: book.pageCount ?? "—" },
                  {
                    label: "Lecture",
                    value: book.readingTime?.replace(" de lecture", "") ?? "—",
                  },
                  { label: "Langue", value: book.language ?? "—" },
                  {
                    label: "Parution",
                    value: book.releaseDate
                      ? new Date(book.releaseDate).getFullYear()
                      : "—",
                  },
                ].map((metric) => (
                  <div key={metric.label} className="bg-paper px-4 py-4">
                    <div className="font-display text-xl text-ink">{metric.value}</div>
                    <div className="text-[0.65rem] uppercase tracking-[0.16em] text-ink/45">
                      {metric.label}
                    </div>
                  </div>
                ))}
              </div>

              {(book.excerpt?.length ?? 0) > 0 && (
                <div id="extrait" className="flex flex-wrap items-center gap-3">
                  <BookExcerptReader book={book} />
                  <span className="text-sm text-ink/45">Feuilletez les premières pages</span>
                </div>
              )}

              {(book.themes?.length ?? 0) > 0 && (
                <div className="flex flex-wrap gap-2">
                  {book.themes?.map((theme) => (
                    <Badge key={theme} variant="outline" size="md">
                      {theme}
                    </Badge>
                  ))}
                </div>
              )}

              {book.authorNote && (
                <blockquote className="border-l-4 border-accent pl-4 font-quote text-lg italic text-ink/80">
                  {book.authorNote}
                </blockquote>
              )}

              <div className="flex justify-center lg:hidden">
                <CatalogBookCover book={book} width={220} />
              </div>
            </section>
          </div>
        </div>
      </section>

      {book.availabilityStatus === "preorder" && book.releaseDate && (
        <section className="bg-midnight py-16 text-white">
          <div className="mx-auto grid max-w-7xl items-center gap-10 px-6 lg:grid-cols-2 lg:px-10">
            <div>
              <span className="eyebrow !text-white/50">Sortie officielle</span>
              <h2 className="mt-4 font-display text-3xl leading-tight tracking-tight sm:text-4xl">
                Disponible le {dateFormatter.format(new Date(book.releaseDate))}
              </h2>
              {book.preorderCampaign?.reserved !== undefined && (
                <p className="mt-4 max-w-md text-white/60">
                  {book.preorderCampaign.reserved.toLocaleString("fr-FR")} lecteurs ont déjà
                  réservé leur exemplaire. Rejoignez la campagne de lancement.
                </p>
              )}
            </div>
            <ReleaseCountdown date={book.releaseDate} />
          </div>
        </section>
      )}

      {aboutParagraphs.length > 0 && (
        <section className="py-24 lg:py-28">
          <div className="mx-auto grid max-w-7xl gap-12 px-6 lg:grid-cols-[0.4fr_0.6fr] lg:gap-16 lg:px-10">
            <Reveal>
              <h2 className="font-display text-4xl leading-tight tracking-tight text-ink lg:text-5xl">
                À propos du livre
              </h2>
            </Reveal>
            <div className="space-y-6">
              {aboutParagraphs.map((paragraph, index) => (
                <Reveal key={index} delay={index}>
                  <p className="text-[1.1rem] leading-relaxed text-ink/70">{paragraph}</p>
                </Reveal>
              ))}
            </div>
          </div>
        </section>
      )}

      <BookReviewSection
        slug={book.slug}
        reviews={book.reviews ?? []}
        averageRating={averageRating}
        reviewCount={reviewCount}
      />

      {(book.relatedBooks?.length ?? 0) > 0 && (
        <section className="py-24 lg:py-28">
          <div className="mx-auto max-w-7xl px-6 lg:px-10">
            <Reveal>
              <h2 className="mb-12 font-display text-3xl tracking-tight text-ink lg:text-4xl">
                Continuer l&apos;exploration
              </h2>
            </Reveal>
            <Stagger className="grid gap-12 sm:grid-cols-2 lg:grid-cols-3 lg:gap-8">
              {book.relatedBooks?.map((relatedBook) => (
                <StaggerItem key={relatedBook.slug}>
                  <RelatedBookCard book={relatedBook} />
                </StaggerItem>
              ))}
            </Stagger>
          </div>
        </section>
      )}

      {purchasableFormats.length === 0 && book.availabilityStatus === "coming" && (
        <div className="mx-auto max-w-xl px-6 pb-16 text-center text-ink/60">
          Ce livre sera bientôt disponible à la vente.
        </div>
      )}
    </div>
  );
}

/**
 * Carte compacte pour un autre livre du catalogue.
 */
function RelatedBookCard({ book }: { book: BookSummary }) {
  return (
    <Link href={`/livres/${book.slug}`} className="group flex items-center gap-5">
      <div className="shrink-0 transition-transform duration-500 group-hover:-translate-y-1">
        <CatalogBookCover book={book} width={96} interactive={false} />
      </div>
      <div>
        <h3 className="font-display text-xl leading-tight text-ink">{book.title}</h3>
        {book.tagline && (
          <p className="mt-1 font-serif-ed text-sm text-ink/55">{book.tagline}</p>
        )}
        <span className="mt-2 inline-block text-xs uppercase tracking-[0.18em] text-accent">
          Découvrir →
        </span>
      </div>
    </Link>
  );
}
