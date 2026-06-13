import type { ApiError } from "@/types/api";

const API_BASE_URL =
  process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1";

/**
 * Options de requête pour le client API.
 */
export interface RequestOptions extends RequestInit {
  token?: string;
  cartSession?: string;
}

/**
 * Client HTTP centralisé pour communiquer avec l'API Laravel.
 */
class ApiClient {
  private baseUrl: string;

  /**
   * Initialise le client avec l'URL de base de l'API.
   *
   * @param baseUrl URL racine de l'API
   */
  constructor(baseUrl: string) {
    this.baseUrl = baseUrl;
  }

  /**
   * Exécute une requête HTTP vers l'API.
   *
   * @param endpoint Chemin relatif
   * @param options Options fetch, token et session panier
   * @returns Données JSON typées
   */
  async request<T>(endpoint: string, options: RequestOptions = {}): Promise<T> {
    const { token, cartSession, headers, ...rest } = options;

    const response = await fetch(`${this.baseUrl}${endpoint}`, {
      ...rest,
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...(cartSession ? { "X-Cart-Session": cartSession } : {}),
        ...headers,
      },
    });

    if (!response.ok) {
      const errorBody = (await response.json().catch(() => ({
        message: "Une erreur est survenue.",
      }))) as ApiError;

      const firstFieldError = errorBody.errors
        ? Object.values(errorBody.errors).flat()[0]
        : undefined;

      throw new Error(firstFieldError ?? errorBody.message);
    }

    return response.json() as Promise<T>;
  }

  /**
   * Effectue une requête GET.
   *
   * @param endpoint Chemin relatif
   * @param options Options optionnelles
   * @returns Données JSON typées
   */
  get<T>(endpoint: string, options: Omit<RequestOptions, "method" | "body"> = {}): Promise<T> {
    return this.request<T>(endpoint, { ...options, method: "GET" });
  }

  /**
   * Effectue une requête POST.
   *
   * @param endpoint Chemin relatif
   * @param body Corps JSON
   * @param options Options optionnelles
   * @returns Données JSON typées
   */
  post<T>(
    endpoint: string,
    body: unknown = {},
    options: Omit<RequestOptions, "method" | "body"> = {},
  ): Promise<T> {
    return this.request<T>(endpoint, {
      ...options,
      method: "POST",
      body: JSON.stringify(body),
    });
  }

  /**
   * Effectue une requête PATCH.
   *
   * @param endpoint Chemin relatif
   * @param body Corps JSON
   * @param options Options optionnelles
   * @returns Données JSON typées
   */
  patch<T>(
    endpoint: string,
    body: unknown,
    options: Omit<RequestOptions, "method" | "body"> = {},
  ): Promise<T> {
    return this.request<T>(endpoint, {
      ...options,
      method: "PATCH",
      body: JSON.stringify(body),
    });
  }

  /**
   * Effectue une requête DELETE.
   *
   * @param endpoint Chemin relatif
   * @param options Options optionnelles
   * @returns Données JSON typées
   */
  delete<T>(endpoint: string, options: Omit<RequestOptions, "method" | "body"> = {}): Promise<T> {
    return this.request<T>(endpoint, { ...options, method: "DELETE" });
  }
}

export const apiClient = new ApiClient(API_BASE_URL);
