/**
 * Adresse postale client.
 */
export interface UserAddress {
  street?: string;
  city?: string;
  commune?: string;
  country?: string;
  phone?: string;
}

/**
 * Profil utilisateur authentifié.
 */
export interface User {
  id: number;
  fullName: string;
  email: string;
  phone?: string | null;
  avatarUrl?: string | null;
  profileAddress?: UserAddress | null;
  deliveryAddress?: UserAddress | null;
  role: string;
  roleLabel: string;
}

/**
 * Réponse après vérification OTP.
 */
export interface AuthResponse {
  message: string;
  token: string;
  user: User;
}
