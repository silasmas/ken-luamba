/**
 * Réponse de l'endpoint de santé de l'API.
 */
export interface HealthResponse {
  status: string;
  service: string;
  version: string;
}

/**
 * Enveloppe générique des réponses API.
 */
export interface ApiResponse<T> {
  data: T;
  message?: string;
}

/**
 * Erreur standard renvoyée par l'API Laravel.
 */
export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}
