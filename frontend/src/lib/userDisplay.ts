/**
 * Extrait les initiales d'un nom complet (2 lettres max).
 *
 * @param fullName Nom complet de l'utilisateur
 * @returns Initiales en majuscules
 */
export function getUserInitials(fullName: string): string {
  const parts = fullName.trim().split(/\s+/).filter(Boolean);

  if (parts.length === 0) {
    return "?";
  }

  if (parts.length === 1) {
    return parts[0].slice(0, 2).toUpperCase();
  }

  return `${parts[0][0] ?? ""}${parts[1][0] ?? ""}`.toUpperCase();
}
