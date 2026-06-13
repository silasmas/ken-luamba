"use client";

import Image from "next/image";
import Link from "next/link";
import { useRef } from "react";
import { motion, useScroll, useTransform } from "framer-motion";
import { ArrowRight, ArrowUpRight, BookOpen } from "lucide-react";
import { Button } from "@/components/ui/button";
import { CatalogBookCover } from "@/components/catalog/CatalogBookCover";
import { resolveMediaUrl } from "@/lib/resolveMediaUrl";

const easePremium = [0.16, 1, 0.3, 1] as const;

const credentials = [
  "Pasteur titulaire du C.M.P.",
  "Master en Théologie",
  "Auteur & conférencier",
];

const titleWords =
  "Des ouvrages qui préparent une génération aux défis de son temps.".split(" ");

const container = {
  hidden: {},
  show: { transition: { staggerChildren: 0.09, delayChildren: 0.15 } },
};

const fadeUp = {
  hidden: { opacity: 0, y: 22 },
  show: { opacity: 1, y: 0, transition: { duration: 0.85, ease: easePremium } },
};

const wordRise = {
  hidden: { y: "115%" },
  show: { y: "0%", transition: { duration: 0.72, ease: easePremium } },
};

interface HeroHomeSectionProps {
  /** Livre mis en avant pour la carte flottante */
  featuredBook: BookSummary | null;
  /** Profil auteur depuis l'API (photo dashboard) */
  author: AuthorDetail | null;
}

/**
 * Hero d'accueil — portrait auteur API + carte dernier ouvrage.
 */
export function HeroHomeSection({ featuredBook, author }: HeroHomeSectionProps) {
  const ref = useRef<HTMLDivElement>(null);
  const authorPhoto = resolveMediaUrl(author?.profileImage);
  const { scrollYProgress } = useScroll({
    target: ref,
    offset: ["start start", "end start"],
  });
  const photoY = useTransform(scrollYProgress, [0, 1], [0, 70]);

  return (
    <section
      ref={ref}
      className="paper-grain relative overflow-hidden bg-paper pb-20 pt-10 lg:pb-28"
    >
      <motion.div
        aria-hidden
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        transition={{ duration: 1.6, ease: "easeOut" }}
        className="pointer-events-none absolute inset-0"
      >
        <motion.div
          animate={{ x: [0, 24, 0], y: [0, -18, 0] }}
          transition={{ duration: 18, repeat: Infinity, ease: "easeInOut" }}
          className="absolute -left-24 top-10 h-72 w-72 rounded-full bg-accent/[0.06] blur-[110px]"
        />
        <motion.div
          animate={{ x: [0, -20, 0], y: [0, 22, 0] }}
          transition={{ duration: 22, repeat: Infinity, ease: "easeInOut" }}
          className="absolute right-0 top-1/3 h-80 w-80 rounded-full bg-midnight/[0.05] blur-[120px]"
        />
      </motion.div>

      <div className="relative mx-auto grid max-w-7xl items-center gap-12 px-6 lg:grid-cols-[1.05fr_0.95fr] lg:gap-12 lg:px-10">
        <motion.div variants={container} initial="hidden" animate="show" className="relative z-10 max-w-2xl pt-6 lg:pt-0">
          <motion.div variants={fadeUp} className="mb-8 flex items-center gap-3">
            <motion.span
              initial={{ scaleX: 0 }}
              animate={{ scaleX: 1 }}
              transition={{ duration: 0.9, ease: easePremium, delay: 0.25 }}
              className="block h-px w-12 origin-left bg-accent"
            />
            <span className="text-[0.7rem] uppercase tracking-[0.28em] text-ink/45">
              Éditions & Enseignements
            </span>
          </motion.div>

          <h1 className="font-display text-[2.7rem] leading-[1.04] tracking-tight text-ink text-balance sm:text-6xl lg:text-[4.2rem]">
            {titleWords.map((word, index) => (
              <span key={`${word}-${index}`} className="mr-[0.22em] inline-block overflow-hidden align-bottom">
                <motion.span variants={wordRise} className="inline-block">
                  {word}
                </motion.span>
              </span>
            ))}
          </h1>

          <motion.p variants={fadeUp} className="mt-7 max-w-xl text-[1.15rem] leading-relaxed text-ink/65">
            Sa mission : préparer une génération à tenir debout dans la foi. À travers ses livres
            et ses enseignements, le Pasteur Ken Luamba transmet une vision enracinée, lucide et
            exigeante — pensée pour éclairer les défis de notre époque.
          </motion.p>

          <motion.div variants={fadeUp} className="mt-10 flex flex-col gap-3 sm:flex-row sm:items-center">
            <Button asChild variant="primary" size="lg">
              <Link href="/livres">
                Découvrir les ouvrages
                <ArrowRight className="h-4 w-4" />
              </Link>
            </Button>
            {featuredBook && (
              <Button asChild variant="outline" size="lg">
                <Link href={`/livres/${featuredBook.slug}#extrait`}>
                  <BookOpen className="h-4 w-4" />
                  Lire un extrait
                </Link>
              </Button>
            )}
          </motion.div>

          <motion.ul
            variants={fadeUp}
            className="mt-12 flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-ink/[0.08] pt-7 text-sm text-ink/60"
          >
            {credentials.map((credential, index) => (
              <li key={credential} className="flex items-center gap-6">
                {index > 0 && <span className="hidden h-1 w-1 rounded-full bg-ink/25 sm:block" />}
                <span>{credential}</span>
              </li>
            ))}
          </motion.ul>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 28, scale: 0.985 }}
          animate={{ opacity: 1, y: 0, scale: 1 }}
          transition={{ duration: 1.1, ease: easePremium, delay: 0.2 }}
          className="relative z-10 mx-auto w-full max-w-md lg:max-w-none"
        >
          <div className="relative pb-16 sm:pb-12">
            <div className="relative aspect-[4/5] w-full overflow-hidden rounded-[2rem] bg-midnight shadow-[0_50px_90px_-50px_rgba(15,23,42,0.7)]">
              <motion.div
                aria-hidden
                animate={{ opacity: [0.18, 0.3, 0.18], scale: [1, 1.12, 1] }}
                transition={{ duration: 9, repeat: Infinity, ease: "easeInOut" }}
                className="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-accent/20 blur-[90px]"
              />
              <Image
                src="/images/logo-kl.png"
                alt=""
                width={320}
                height={160}
                className="pointer-events-none absolute right-6 top-6 z-10 w-24 opacity-15"
              />
              <motion.div style={{ y: photoY }} className="absolute inset-0">
                {authorPhoto ? (
                  <Image
                    src={authorPhoto}
                    alt={author.fullName}
                    fill
                    priority
                    sizes="(max-width: 1024px) 90vw, 45vw"
                    className="object-cover object-top"
                  />
                ) : (
                  <div className="flex h-full items-center justify-center bg-midnight text-white/40">
                    Photo auteur
                  </div>
                )}
              </motion.div>
              <div className="absolute inset-x-0 top-0 h-1/4 bg-gradient-to-b from-midnight via-midnight/40 to-transparent" />
              <div className="absolute left-7 top-7 z-10">
                <p className="font-display text-2xl leading-none text-white">Ken Luamba</p>
                <p className="mt-1.5 text-[0.7rem] uppercase tracking-[0.24em] text-white/55">
                  {author?.title ?? "Pasteur · Auteur · Enseignant"}
                </p>
              </div>
            </div>

            {featuredBook && (
              <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.9, ease: easePremium, delay: 0.7 }}
                className="absolute -bottom-2 left-4 right-4 sm:left-auto sm:right-6 sm:w-80"
              >
                <motion.div
                  animate={{ y: [0, -7, 0] }}
                  transition={{ duration: 6, repeat: Infinity, ease: "easeInOut" }}
                >
                  <Link
                    href={`/livres/${featuredBook.slug}`}
                    className="group flex items-center gap-4 rounded-2xl border border-ink/[0.07] bg-paper/90 p-3.5 shadow-[0_30px_60px_-30px_rgba(10,10,10,0.45)] backdrop-blur-md transition-colors hover:border-ink/15"
                  >
                    <div className="shrink-0">
                      <CatalogBookCover book={featuredBook} width={62} interactive={false} />
                    </div>
                    <div className="min-w-0 flex-1">
                      <p className="text-[0.6rem] uppercase tracking-[0.2em] text-accent">
                        Dernier ouvrage
                        {featuredBook.availabilityLabel ? ` · ${featuredBook.availabilityLabel}` : ""}
                      </p>
                      <p className="mt-1 truncate font-display text-[1.05rem] leading-tight text-ink">
                        {featuredBook.title}
                      </p>
                      <span className="mt-1 inline-flex items-center gap-1 text-xs text-ink/50 transition-colors group-hover:text-ink">
                        Découvrir <ArrowUpRight className="h-3 w-3" />
                      </span>
                    </div>
                  </Link>
                </motion.div>
              </motion.div>
            )}
          </div>
        </motion.div>
      </div>
    </section>
  );
}
