"use client";

import { useEffect, useState } from "react";
import { AnimatePresence, motion } from "framer-motion";
import { BookOpenText, ChevronLeft, ChevronRight, X } from "lucide-react";
import { Button } from "@/components/ui/button";
import { CatalogBookCover } from "@/components/catalog/CatalogBookCover";
import type { BookDetail, ExcerptPage } from "@/types/catalog";

/**
 * Lecteur modal d'extrait feuilletable (sans dépendance externe).
 */
export function BookExcerptReader({ book }: { book: BookDetail }) {
  const pages = book.excerpt ?? [];
  const [open, setOpen] = useState(false);
  const [pageIndex, setPageIndex] = useState(0);

  useEffect(() => {
    if (!open) {
      return;
    }

    const onKey = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        setOpen(false);
      }
      if (event.key === "ArrowLeft") {
        setPageIndex((index) => Math.max(0, index - 1));
      }
      if (event.key === "ArrowRight") {
        setPageIndex((index) => Math.min(pages.length - 1, index + 1));
      }
    };

    window.addEventListener("keydown", onKey);
    document.body.style.overflow = "hidden";

    return () => {
      window.removeEventListener("keydown", onKey);
      document.body.style.overflow = "";
    };
  }, [open, pages.length]);

  if (pages.length === 0) {
    return null;
  }

  const currentPage = pages[pageIndex];

  return (
    <>
      <Button variant="outline" size="lg" onClick={() => setOpen(true)}>
        <BookOpenText className="h-4 w-4" />
        Lire un extrait
      </Button>

      <AnimatePresence>
        {open && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 z-[100] flex flex-col bg-midnight/95 backdrop-blur-xl"
          >
            <div className="flex items-center justify-between px-5 py-4 text-white sm:px-8">
              <div className="flex items-center gap-3">
                <BookOpenText className="h-4 w-4 text-white/60" />
                <div className="leading-tight">
                  <p className="text-sm font-medium">{book.title}</p>
                  <p className="text-[0.7rem] uppercase tracking-[0.2em] text-white/45">
                    Aperçu · Feuilleter
                  </p>
                </div>
              </div>
              <button
                type="button"
                onClick={() => setOpen(false)}
                className="inline-flex h-10 w-10 items-center justify-center rounded-full text-white/70 transition-colors hover:bg-white/10 hover:text-white"
                aria-label="Fermer"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            <div className="relative flex flex-1 items-center justify-center overflow-hidden px-4">
              <button
                type="button"
                onClick={() => setPageIndex((index) => Math.max(0, index - 1))}
                disabled={pageIndex === 0}
                className="absolute left-2 z-10 inline-flex h-12 w-12 items-center justify-center rounded-full bg-white/5 text-white/70 backdrop-blur-sm transition-all hover:bg-white/15 hover:text-white disabled:opacity-30 sm:left-8"
                aria-label="Page précédente"
              >
                <ChevronLeft className="h-5 w-5" />
              </button>

              <div className="h-[min(70vh,560px)] w-[min(92vw,420px)] overflow-hidden rounded-md bg-[#fbfaf6] shadow-2xl">
                <ExcerptPageView page={currentPage} book={book} pageNumber={pageIndex + 1} />
              </div>

              <button
                type="button"
                onClick={() => setPageIndex((index) => Math.min(pages.length - 1, index + 1))}
                disabled={pageIndex >= pages.length - 1}
                className="absolute right-2 z-10 inline-flex h-12 w-12 items-center justify-center rounded-full bg-white/5 text-white/70 backdrop-blur-sm transition-all hover:bg-white/15 hover:text-white disabled:opacity-30 sm:right-8"
                aria-label="Page suivante"
              >
                <ChevronRight className="h-5 w-5" />
              </button>
            </div>

            <div className="flex flex-col items-center gap-3 px-6 py-6 text-white">
              <div className="h-1 w-48 overflow-hidden rounded-full bg-white/10">
                <div
                  className="h-full rounded-full bg-white/70 transition-all duration-500"
                  style={{ width: `${((pageIndex + 1) / pages.length) * 100}%` }}
                />
              </div>
              <p className="text-xs text-white/50">
                Page {pageIndex + 1} sur {pages.length}
              </p>
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </>
  );
}

/**
 * Affiche une page d'extrait selon son type.
 */
function ExcerptPageView({
  page,
  book,
  pageNumber,
}: {
  page: ExcerptPage;
  book: BookDetail;
  pageNumber: number;
}) {
  if (page.kind === "cover") {
    return (
      <div className="flex h-full w-full items-center justify-center bg-gradient-to-b from-mist/40 to-[#fbfaf6] p-6">
        <CatalogBookCover book={book} width={150} interactive={false} />
      </div>
    );
  }

  if (page.kind === "chapter") {
    return (
      <div className="flex h-full w-full flex-col items-center justify-center px-8 text-center text-ink">
        <span className="eyebrow mb-6">{page.chapter}</span>
        <div className="mb-6 h-px w-12 bg-ink/20" />
        <h3 className="font-display text-3xl leading-tight tracking-tight sm:text-4xl">
          {page.title}
        </h3>
      </div>
    );
  }

  return (
    <div className="flex h-full w-full flex-col px-7 py-9 text-ink sm:px-10 sm:py-12">
      {page.title && <h4 className="mb-4 font-display text-xl">{page.title}</h4>}
      <div className="space-y-4 font-quote text-[1.08rem] leading-[1.7] text-ink/80">
        {page.paragraphs?.map((paragraph, index) => (
          <p key={index}>{paragraph}</p>
        ))}
      </div>
      <div className="mt-auto flex items-center justify-between pt-6 text-[0.65rem] uppercase tracking-[0.18em] text-ink/35">
        <span>Ken Luamba</span>
        <span>{page.pageLabel ?? pageNumber}</span>
      </div>
    </div>
  );
}
