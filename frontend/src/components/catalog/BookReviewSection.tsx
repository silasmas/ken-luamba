"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { MessageSquareQuote, Quote, Star } from "lucide-react";
import { Button } from "@/components/ui/button";
import { submitBookReview } from "@/lib/api/reviews";
import { useAuthStore } from "@/stores/authStore";
import type { BookReview } from "@/types/catalog";

/**
 * Section avis lecteurs avec formulaire de témoignage modéré.
 */
export function BookReviewSection({
  slug,
  reviews,
  averageRating,
  reviewCount,
}: {
  slug: string;
  reviews: BookReview[];
  averageRating: number | null;
  reviewCount: number;
}) {
  const router = useRouter();
  const token = useAuthStore((state) => state.token);
  const user = useAuthStore((state) => state.user);
  const isAuthReady = useAuthStore((state) => state.isReady);

  const [showForm, setShowForm] = useState(false);
  const [rating, setRating] = useState(5);
  const [content, setContent] = useState("");
  const [authorRole, setAuthorRole] = useState("");
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(false);

  /**
   * Ouvre le formulaire ou redirige vers la connexion.
   */
  const handleOpenForm = () => {
    if (!token) {
      router.push(`/connexion?redirect=/livres/${slug}#temoignage`);
      return;
    }

    setShowForm(true);
  };

  /**
   * Soumet un témoignage pour modération admin.
   *
   * @param event Événement de soumission du formulaire
   */
  const handleSubmit = async (event: React.FormEvent) => {
    event.preventDefault();

    if (!token) {
      router.push(`/connexion?redirect=/livres/${slug}#temoignage`);
      return;
    }

    if (content.trim().length < 20) {
      setError("Votre témoignage doit contenir au moins 20 caractères.");
      return;
    }

    setIsLoading(true);
    setError(null);
    setMessage(null);

    try {
      const response = await submitBookReview(token, slug, {
        rating,
        content: content.trim(),
        authorRole: authorRole || undefined,
      });
      setMessage(response.message);
      setContent("");
      setAuthorRole("");
      setShowForm(false);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Erreur lors de l'envoi");
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <section
      id="temoignage"
      className="border-t border-ink/[0.06] bg-paper py-24 lg:py-28"
    >
      <div className="mx-auto max-w-7xl px-6 lg:px-10">
        <span className="eyebrow">Avis des lecteurs</span>
        <h2 className="mt-4 max-w-2xl font-display text-4xl leading-tight tracking-tight text-ink lg:text-5xl">
          Ce qu&apos;en disent celles et ceux qui l&apos;ont lu.
        </h2>

        {reviewCount > 0 && averageRating !== null && (
          <p className="mt-4 text-sm text-ink/55">
            {averageRating.toFixed(1)} / 5 · {reviewCount} avis publiés
          </p>
        )}

        {reviews.length > 0 && (
          <div className="mt-14 grid gap-6 md:grid-cols-3">
            {reviews.map((review) => (
              <figure
                key={review.id}
                className="flex h-full flex-col rounded-3xl border border-ink/[0.08] bg-white/50 p-7"
              >
                <Quote className="h-7 w-7 text-accent/25" />
                <blockquote className="mt-4 flex-1 font-quote text-[1.2rem] leading-relaxed text-ink/80">
                  « {review.content} »
                </blockquote>
                <figcaption className="mt-6 flex items-center justify-between border-t border-ink/[0.07] pt-5">
                  <div>
                    <div className="text-sm font-medium text-ink">{review.authorName}</div>
                    {review.authorRole && (
                      <div className="text-xs text-ink/45">{review.authorRole}</div>
                    )}
                  </div>
                  <div className="flex gap-0.5">
                    {Array.from({ length: review.rating }).map((_, index) => (
                      <Star key={index} className="h-3.5 w-3.5 fill-accent text-accent" />
                    ))}
                  </div>
                </figcaption>
              </figure>
            ))}
          </div>
        )}

        <div className="mt-16 max-w-xl rounded-3xl border border-ink/[0.08] bg-white/50 p-7">
          <h3 className="font-display text-xl text-ink">Partager votre témoignage</h3>
          <p className="mt-2 text-sm text-ink/60">
            Votre avis sera publié après validation par notre équipe.
          </p>

          {!showForm && (
            <div className="mt-6">
              <Button type="button" variant="accent" size="lg" onClick={handleOpenForm}>
                <MessageSquareQuote className="h-4 w-4" />
                Laisser mon témoignage
              </Button>
              {isAuthReady && !token && (
                <p className="mt-3 text-sm text-ink/55">
                  Vous serez invité à vous connecter avant l&apos;envoi.
                </p>
              )}
            </div>
          )}

          {showForm && token && user && (
            <form onSubmit={(event) => void handleSubmit(event)} className="mt-6 space-y-4">
              <div>
                <p className="text-sm font-medium text-ink">Votre note</p>
                <div className="mt-2 flex gap-1">
                  {Array.from({ length: 5 }).map((_, index) => (
                    <button
                      key={index}
                      type="button"
                      onClick={() => setRating(index + 1)}
                      className="p-1"
                      aria-label={`Noter ${index + 1} sur 5`}
                    >
                      <Star
                        className={
                          index < rating
                            ? "h-5 w-5 fill-accent text-accent"
                            : "h-5 w-5 text-ink/20"
                        }
                      />
                    </button>
                  ))}
                </div>
              </div>
              <input
                type="text"
                placeholder="Votre rôle (optionnel)"
                value={authorRole}
                onChange={(event) => setAuthorRole(event.target.value)}
                className="h-11 w-full rounded-full border border-ink/15 bg-white/60 px-4 text-sm outline-none"
              />
              <textarea
                required
                minLength={20}
                rows={4}
                placeholder="Votre témoignage (minimum 20 caractères)..."
                value={content}
                onChange={(event) => setContent(event.target.value)}
                className="w-full rounded-2xl border border-ink/15 bg-white/60 px-4 py-3 text-sm outline-none"
              />
              {error && <p className="text-sm text-red-600">{error}</p>}
              {message && <p className="text-sm text-green-700">{message}</p>}
              <div className="flex flex-wrap gap-3">
                <Button type="submit" variant="primary" size="lg" disabled={isLoading}>
                  {isLoading ? "Envoi..." : "Envoyer mon témoignage"}
                </Button>
                <Button
                  type="button"
                  variant="ghost"
                  size="lg"
                  onClick={() => setShowForm(false)}
                >
                  Annuler
                </Button>
              </div>
            </form>
          )}
        </div>
      </div>
    </section>
  );
}
