const AUTH_TOKEN_KEY = "ken-luamba-auth-token";

/**
 * Lit le token Sanctum depuis le stockage local.
 *
 * @returns Token ou null
 */
export function getAuthToken(): string | null {
  if (typeof window === "undefined") {
    return null;
  }

  return localStorage.getItem(AUTH_TOKEN_KEY);
}

/**
 * Persiste le token d'authentification.
 *
 * @param token Token Sanctum
 */
export function setAuthToken(token: string): void {
  localStorage.setItem(AUTH_TOKEN_KEY, token);
}

/**
 * Supprime le token d'authentification.
 */
export function clearAuthToken(): void {
  localStorage.removeItem(AUTH_TOKEN_KEY);
}
