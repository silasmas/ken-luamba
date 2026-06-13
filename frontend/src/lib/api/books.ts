import { apiClient } from "@/lib/api/client";
import type { PaginatedResponse } from "@/types/catalog";
import type { AuthorDetail, BookDetail, BookSummary } from "@/types/catalog";

/**
 * Récupère la liste paginée des livres publiés.
 *
 * @param params Paramètres de filtre optionnels
 * @returns Liste paginée de livres
 */
export async function fetchBooks(params?: {
  featured?: boolean;
  author?: string;
  page?: number;
}): Promise<PaginatedResponse<BookSummary>> {
  const searchParams = new URLSearchParams();

  if (params?.featured) {
    searchParams.set("featured", "1");
  }

  if (params?.author) {
    searchParams.set("author", params.author);
  }

  if (params?.page) {
    searchParams.set("page", String(params.page));
  }

  const query = searchParams.toString();

  return apiClient.get<PaginatedResponse<BookSummary>>(
    `/books${query ? `?${query}` : ""}`,
  );
}

/**
 * Récupère le détail d'un livre par slug.
 *
 * @param slug Identifiant URL du livre
 * @returns Détail du livre
 */
export async function fetchBook(slug: string): Promise<{ data: BookDetail }> {
  return apiClient.get<{ data: BookDetail }>(`/books/${slug}`);
}

/**
 * Récupère le profil public d'un auteur.
 *
 * @param slug Identifiant URL de l'auteur
 * @returns Profil auteur
 */
export async function fetchAuthor(slug: string): Promise<{ data: AuthorDetail }> {
  return apiClient.get<{ data: AuthorDetail }>(`/authors/${slug}`);
}
