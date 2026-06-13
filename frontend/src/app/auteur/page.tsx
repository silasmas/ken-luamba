import Link from "next/link";
import Image from "next/image";
import { BookCard } from "@/components/shop/BookCard";
import { Reveal } from "@/components/motion/reveal";
import { Button } from "@/components/ui/button";
import { apiClient } from "@/lib/api/client";
import { resolveMediaUrl } from "@/lib/resolveMediaUrl";
import type { AuthorDetail } from "@/types/catalog";

export const dynamic = "force-dynamic";

/**
 * Page dédiée au pasteur Ken Luamba — habillage éditorial.
 */
export default async function AuteurPage() {
  const response = await apiClient.get<{ data: AuthorDetail }>("/authors/ken-luamba");
  const author = response.data;
  const authorPhoto = resolveMediaUrl(author.profileImage);

  return (
    <div className="bg-paper pb-24">
      <section className="border-b border-ink/[0.06] bg-midnight py-16 text-white lg:py-24">
        <div className="mx-auto grid max-w-7xl items-center gap-10 px-6 lg:grid-cols-[1fr_320px] lg:px-10">
          <div>
            <Reveal>
              <span className="eyebrow !text-white/50">L&apos;auteur</span>
            </Reveal>
            <Reveal delay={1}>
              <h1 className="mt-4 font-display text-4xl tracking-tight lg:text-6xl">
                {author.fullName}
              </h1>
            </Reveal>
            <Reveal delay={2}>
              <p className="mt-3 text-lg text-white/70">{author.title}</p>
            </Reveal>
            {author.featuredQuote && (
              <Reveal delay={3}>
                <blockquote className="mt-8 max-w-2xl border-l-4 border-accent pl-4 font-quote text-xl italic text-white/85">
                  {author.featuredQuote}
                </blockquote>
              </Reveal>
            )}
          </div>
          {authorPhoto && (
            <Reveal delay={2} className="relative mx-auto aspect-[4/5] w-full max-w-xs overflow-hidden rounded-[2rem]">
              <Image
                src={authorPhoto}
                alt={author.fullName}
                fill
                className="object-cover object-top"
                sizes="320px"
              />
            </Reveal>
          )}
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-6 py-16 lg:px-10">
        <div className="grid gap-10 lg:grid-cols-[2fr_1fr]">
          <div>
            <h2 className="font-display text-3xl text-ink">Biographie</h2>
            <p className="mt-4 leading-relaxed text-ink/70">{author.fullBio ?? author.shortBio}</p>
          </div>
          <aside className="rounded-3xl border border-ink/[0.08] bg-white/60 p-6">
            <h3 className="font-display text-lg text-ink">Réseaux</h3>
            <ul className="mt-4 space-y-2 text-sm">
              {Object.entries(author.socialLinks ?? {}).map(([platform, url]) => (
                <li key={platform}>
                  <a href={url} className="text-accent hover:underline" target="_blank" rel="noreferrer">
                    {platform}
                  </a>
                </li>
              ))}
            </ul>
          </aside>
        </div>
      </section>

      {author.books && author.books.length > 0 && (
        <section className="mx-auto max-w-7xl px-6 pb-16 lg:px-10">
          <h2 className="font-display text-3xl text-ink">Ses ouvrages</h2>
          <div className="mt-10 grid gap-12 sm:grid-cols-2 lg:grid-cols-3">
            {author.books.map((book) => (
              <BookCard key={book.id} book={book} variant="editorial" />
            ))}
          </div>
        </section>
      )}

      <div className="mx-auto max-w-7xl px-6 lg:px-10">
        <Button asChild variant="outline" size="md">
          <Link href="/livres">Voir tout le catalogue</Link>
        </Button>
      </div>
    </div>
  );
}
