import Link from "next/link";
import { Sparkles, Check, ArrowRight } from "lucide-react";
import { Button } from "@/components/ui/button";
import { ReleaseCountdown } from "@/components/catalog/ReleaseCountdown";
import { Reveal } from "@/components/motion/reveal";
import type { BookSummary } from "@/types/catalog";

const dateFormatter = new Intl.DateTimeFormat("fr-FR", {
  day: "numeric",
  month: "long",
  year: "numeric",
});

interface LaunchCampaignSectionProps {
  /** Livre en précommande avec campagne configurée */
  book: BookSummary;
}

/**
 * Section campagne de lancement — countdown, barre de progression, avantages.
 */
export function LaunchCampaignSection({ book }: LaunchCampaignSectionProps) {
  const campaign = book.preorderCampaign;

  if (book.availabilityStatus !== "preorder" || !campaign?.goal || !book.releaseDate) {
    return null;
  }

  const reserved = campaign.reserved ?? 0;
  const goal = campaign.goal;
  const pct = Math.min(100, Math.round((reserved / goal) * 100));
  const bonuses = campaign.bonuses ?? [];

  return (
    <section className="relative overflow-hidden bg-midnight py-24 text-white lg:py-32">
      <div className="pointer-events-none absolute -left-40 top-0 h-96 w-96 rounded-full bg-accent/20 blur-[120px]" />
      <div className="pointer-events-none absolute -right-32 bottom-0 h-96 w-96 rounded-full bg-accent/10 blur-[120px]" />

      <div className="relative mx-auto max-w-5xl px-6 text-center lg:px-10">
        <Reveal>
          <div className="mx-auto mb-6 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-1.5 text-[0.7rem] uppercase tracking-[0.24em] text-white/70">
            <Sparkles className="h-3.5 w-3.5 text-accent" />
            Campagne de lancement
          </div>
        </Reveal>

        <Reveal delay={1}>
          <h2 className="font-display text-4xl leading-[1.05] tracking-tight text-balance sm:text-5xl lg:text-[3.6rem]">
            La précommande est ouverte.
          </h2>
        </Reveal>

        <Reveal delay={2}>
          <p className="mx-auto mt-5 max-w-xl font-serif-ed text-[1.2rem] leading-relaxed text-white/65">
            « {book.title} » paraît le{" "}
            <span className="text-white">{dateFormatter.format(new Date(book.releaseDate))}</span>.
            Réservez votre exemplaire et rejoignez les premiers lecteurs.
          </p>
        </Reveal>

        <Reveal delay={3}>
          <ReleaseCountdown date={book.releaseDate} className="mx-auto mt-12 max-w-xl" />
        </Reveal>

        <Reveal delay={4}>
          <div className="mx-auto mt-12 max-w-xl">
            <div className="mb-3 flex items-baseline justify-between text-sm">
              <span className="text-white/70">
                <span className="font-display text-xl text-white">
                  {reserved.toLocaleString("fr-FR")}
                </span>{" "}
                exemplaires réservés
              </span>
              <span className="text-white/50">Objectif {goal.toLocaleString("fr-FR")}</span>
            </div>
            <div className="h-2 w-full overflow-hidden rounded-full bg-white/10">
              <div
                className="h-full rounded-full bg-gradient-to-r from-accent to-blue-400 transition-all duration-1000"
                style={{ width: `${pct}%` }}
              />
            </div>
            <p className="mt-2 text-right text-xs text-white/45">{pct}% de l&apos;objectif atteint</p>
          </div>
        </Reveal>

        {bonuses.length > 0 && (
          <Reveal delay={5}>
            <div className="mx-auto mt-12 grid max-w-3xl gap-3 sm:grid-cols-3">
              {bonuses.map((bonus) => (
                <div
                  key={bonus}
                  className="flex items-start gap-2.5 rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-4 text-left text-sm text-white/75 backdrop-blur-sm"
                >
                  <Check className="mt-0.5 h-4 w-4 shrink-0 text-accent" />
                  {bonus}
                </div>
              ))}
            </div>
          </Reveal>
        )}

        <div className="mt-12">
          <Button asChild variant="accent" size="lg">
            <Link href={`/livres/${book.slug}#precommande`}>
              Précommander l&apos;ouvrage
              <ArrowRight className="h-4 w-4" />
            </Link>
          </Button>
        </div>
      </div>
    </section>
  );
}
