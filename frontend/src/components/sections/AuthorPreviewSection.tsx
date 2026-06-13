import Image from "next/image";
import Link from "next/link";
import { ArrowRight, Quote } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Reveal } from "@/components/motion/reveal";
import { resolveMediaUrl } from "@/lib/resolveMediaUrl";
import type { AuthorDetail } from "@/types/catalog";

interface AuthorPreviewSectionProps {
  /** Profil auteur depuis l'API */
  author: AuthorDetail | null;
}

/**
 * Aperçu auteur sur la page d'accueil — photo dashboard + citation.
 */
export function AuthorPreviewSection({ author }: AuthorPreviewSectionProps) {
  if (!author) {
    return null;
  }

  const authorPhoto = resolveMediaUrl(author.profileImage);

  return (
    <section className="relative overflow-hidden bg-paper py-24 lg:py-28">
      <div className="mx-auto max-w-7xl px-6 lg:px-10">
        <div className="grid items-center gap-12 lg:grid-cols-[0.9fr_1.1fr] lg:gap-16">
          <Reveal className="relative">
            <div className="relative mx-auto aspect-[4/5] w-full max-w-sm overflow-hidden rounded-[1.75rem] bg-gradient-to-b from-mist/60 to-paper">
              {authorPhoto ? (
                <Image
                  src={authorPhoto}
                  alt={author.fullName}
                  fill
                  sizes="(max-width: 1024px) 90vw, 40vw"
                  className="object-cover object-top"
                />
              ) : (
                <div className="flex h-full items-center justify-center text-ink/40">Photo auteur</div>
              )}
              <div className="absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-ink/30 to-transparent" />
            </div>
          </Reveal>

          <div>
            <Reveal>
              <span className="eyebrow">L&apos;auteur</span>
            </Reveal>
            <Reveal delay={1}>
              <Quote className="mt-6 h-9 w-9 text-accent/30" />
            </Reveal>
            {author.featuredQuote && (
              <Reveal delay={2}>
                <blockquote className="mt-4 font-quote text-3xl leading-[1.2] tracking-tight text-ink text-balance sm:text-4xl lg:text-[2.7rem]">
                  « {author.featuredQuote} »
                </blockquote>
              </Reveal>
            )}
            <Reveal delay={3}>
              <p className="mt-7 max-w-xl text-[1.02rem] leading-relaxed text-ink/60">
                {author.shortBio}
              </p>
            </Reveal>
            <Reveal delay={4}>
              <p className="mt-6 text-sm font-medium text-ink/70">
                Ken Luamba — {author.title ?? "Auteur, Conférencier et Visionnaire"}
              </p>
            </Reveal>
            <Button asChild variant="primary" size="lg" className="mt-10">
              <Link href="/auteur">
                En savoir plus sur l&apos;auteur
                <ArrowRight className="h-4 w-4" />
              </Link>
            </Button>
          </div>
        </div>
      </div>
    </section>
  );
}
