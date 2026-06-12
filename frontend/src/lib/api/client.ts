import type { ApiError } from "@/types/api";

const API_BASE_URL =
  process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api/v1";

/**
 * Options de requête pour le client API.
 */
interface RequestOptions extends RequestInit {
  token?: string;
}

/**
 * Client HTTP centralisé pour communiquer avec l'API Laravel.
 */
class ApiClient {
  private baseUrl: string;

  /**
   * Initialise le client avec l'URL de base de l'API.
   *
   * @param baseUrl URL racine de l'API (ex. /api/v1)
   */
  constructor(baseUrl: string) {
    this.baseUrl = baseUrl;
  }

  /**
   * Exécute une requête HTTP vers l'API.
   *
   * @param endpoint Chemin relatif (ex. /health)
   * @param options Options fetch et token d'authentification optionnel
   * @returns Données JSON typées
   */
  async request<T>(endpoint: string, options: RequestOptions = {}): Promise<T> {
    const { token, headers, ...rest } = options;

    const response = await fetch(`${this.baseUrl}${endpoint}`, {
      ...rest,
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...headers,
      },
    });

    if (!response.ok) {
      const error = (await response.json().catch(() => ({
        message: "Une erreur est survenue.",
      }))) as ApiError;

      throw new Error(error.message);
    }

    return response.json() as Promise<T>;
  }

  /**
   * Effectue une requête GET.
   *
   * @param endpoint Chemin relatif de l'endpoint
   * @param token Token Sanctum optionnel
   * @returns Données JSON typées
   */
  get<T>(endpoint: string, token?: string): Promise<T> {
    return this.request<T>(endpoint, { method: "GET", token });
  }

  /**
   * Effectue une requête POST.
   *
   * @param endpoint Chemin relatif de l'endpoint
   * @param body Corps de la requête
   * @param token Token Sanctum optionnel
   * @returns Données JSON typées
   */
  post<T>(endpoint: string, body: unknown, token?: string): Promise<T> {
    return this.request<T>(endpoint, {
      method: "POST",
      body: JSON.stringify(body),
      token,
    });
  }
}

export const apiClient = new ApiClient(API_BASE_URL);
