/**
 * Extrait l'origine du backend (sans /api/v1) depuis les variables d'environnement.
 *
 * @returns Origine du serveur Laravel (ex. http://localhost:8001)
 */
export function getMediaOrigin(): string {
  const apiUrl = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8001/api/v1";
  return apiUrl.replace(/\/api\/v1\/?$/, "");
}

/**
 * Normalise une URL média renvoyée par l'API pour éviter les écarts localhost / 127.0.0.1.
 *
 * @param url URL absolue ou relative renvoyée par Laravel
 * @returns URL utilisable par next/image ou null
 */
export function resolveMediaUrl(url: string | null | undefined): string | null {
  if (!url) {
    return null;
  }

  const origin = getMediaOrigin();

  if (url.startsWith("/")) {
    return `${origin}${url}`;
  }

  try {
    const parsed = new URL(url);
    return `${origin}${parsed.pathname}${parsed.search}`;
  } catch {
    return url;
  }
}
