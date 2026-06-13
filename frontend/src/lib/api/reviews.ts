import { apiClient } from "@/lib/api/client";
import type { BookReview } from "@/types/catalog";

/**
 * Soumet un témoignage lecteur en attente de validation.
 *
 * @param token Jeton Sanctum
 * @param slug Slug du livre
 * @param payload Note et contenu
 */
export async function submitBookReview(
  token: string,
  slug: string,
  payload: { rating: number; content: string; authorRole?: string },
): Promise<{ message: string }> {
  return apiClient.post<{ message: string }>(`/books/${slug}/reviews`, payload, {
    token,
  });
}

/**
 * Récupère les témoignages approuvés d'un livre.
 *
 * @param slug Slug du livre
 */
export async function fetchBookReviews(slug: string): Promise<{
  data: BookReview[];
  meta: { count: number; averageRating: number | null };
}> {
  return apiClient.get(`/books/${slug}/reviews`);
}
