import { apiClient } from "@/lib/api/client";
import { getCartSessionId } from "@/lib/api/cart";
import { clearAuthToken, getAuthToken, setAuthToken } from "@/lib/authToken";
import type { AuthResponse, User, UserAddress } from "@/types/auth";

/**
 * Demande un code OTP pour inscription.
 *
 * @param email Email du client
 * @param fullName Nom complet
 */
export async function requestRegisterOtp(email: string, fullName: string): Promise<void> {
  await apiClient.post("/auth/register", { email, fullName });
}

/**
 * Demande un code OTP pour connexion.
 *
 * @param email Email du client
 */
export async function requestLoginOtp(email: string): Promise<void> {
  await apiClient.post("/auth/login", { email });
}

/**
 * Vérifie le code OTP et stocke le token.
 *
 * @param email Email du client
 * @param code Code OTP
 * @param type register | login
 * @returns Token et profil
 */
export async function verifyOtp(
  email: string,
  code: string,
  type: "register" | "login",
): Promise<AuthResponse> {
  const response = await apiClient.post<AuthResponse>(
    "/auth/verify-otp",
    { email, code, type },
    { cartSession: getCartSessionId() ?? undefined },
  );

  setAuthToken(response.token);

  return response;
}

/**
 * Récupère le profil de l'utilisateur connecté.
 *
 * @param token Token Sanctum
 * @returns Profil utilisateur
 */
export async function fetchMe(token: string): Promise<User> {
  const response = await apiClient.get<{ data: User }>("/auth/me", { token });

  return response.data;
}

/**
 * Met à jour le profil client.
 *
 * @param token Token Sanctum
 * @param payload Champs profil
 */
export async function updateProfile(
  token: string,
  payload: {
    fullName?: string;
    phone?: string | null;
    profileAddress?: UserAddress | null;
    deliveryAddress?: UserAddress | null;
  },
): Promise<{ message: string; data: User }> {
  return apiClient.patch("/auth/me", payload, { token });
}

/**
 * Met à jour la photo de profil.
 *
 * @param token Token Sanctum
 * @param file Fichier image
 */
export async function updateAvatar(
  token: string,
  file: File,
): Promise<{ message: string; data: User }> {
  const formData = new FormData();
  formData.append("avatar", file);

  const response = await fetch(
    `${process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8001/api/v1"}/auth/me/avatar`,
    {
      method: "POST",
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
      },
      body: formData,
    },
  );

  if (!response.ok) {
    const errorBody = (await response.json().catch(() => ({
      message: "Erreur lors de l'envoi de la photo.",
    }))) as { message?: string };

    throw new Error(errorBody.message ?? "Erreur lors de l'envoi de la photo.");
  }

  return response.json() as Promise<{ message: string; data: User }>;
}

/**
 * Déconnecte l'utilisateur et supprime le token local.
 *
 * @param token Token Sanctum
 */
export async function logout(token: string): Promise<void> {
  await apiClient.post("/auth/logout", {}, { token });
  clearAuthToken();
}

/**
 * Retourne le token courant s'il existe.
 */
export function getStoredToken(): string | null {
  return getAuthToken();
}
