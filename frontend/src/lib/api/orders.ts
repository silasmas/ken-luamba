import { apiClient } from "@/lib/api/client";
import { getCartSessionId } from "@/lib/api/cart";
import type { Order, PaymentInitResult, PaymentStatusResult, PickupPoint } from "@/types/order";

/**
 * Récupère une commande par son numéro.
 *
 * @param token Token Sanctum
 * @param orderNumber Numéro de commande
 */
export async function fetchOrder(token: string, orderNumber: string): Promise<Order> {
  const response = await apiClient.get<{ data: Order }>(`/orders/${orderNumber}`, { token });

  return response.data;
}

/**
 * Liste les commandes du client.
 *
 * @param token Token Sanctum
 */
export async function fetchOrders(token: string): Promise<Order[]> {
  const response = await apiClient.get<{ data: Order[] }>("/orders", { token });

  return response.data;
}

/**
 * Liste les points de retrait actifs.
 */
export async function fetchPickupPoints(): Promise<PickupPoint[]> {
  const response = await apiClient.get<{ data: PickupPoint[] }>("/pickup-points");

  return response.data;
}

/**
 * Crée une commande depuis le panier.
 *
 * @param token Token Sanctum
 * @param payload Données de checkout
 */
export async function createOrder(
  token: string,
  payload: {
    fulfillmentType?: "delivery" | "pickup";
    pickupPointId?: string;
    shippingAddress?: {
      street: string;
      city: string;
      commune?: string;
      country: string;
      phone: string;
    };
    notes?: string;
  },
): Promise<{ message: string; data: Order }> {
  return apiClient.post(
    "/orders",
    payload,
    { token, cartSession: getCartSessionId() ?? undefined },
  );
}

/**
 * Lance le paiement pour une commande.
 *
 * @param token Token Sanctum
 * @param orderNumber Numéro de commande
 * @param channel mobile_money | card
 * @param phone Téléphone Mobile Money
 * @param providerCode Code opérateur mobile
 */
export async function initiatePayment(
  token: string,
  orderNumber: string,
  channel: "mobile_money" | "card",
  phone?: string,
  providerCode?: string,
): Promise<{ message: string; data: PaymentInitResult }> {
  return apiClient.post(
    `/orders/${orderNumber}/pay`,
    { channel, phone, providerCode },
    { token },
  );
}

/**
 * Vérifie le statut d'un paiement (polling).
 *
 * @param reference Référence commande ou FlexPay
 */
export async function checkPaymentStatus(
  reference: string,
): Promise<{ data: PaymentStatusResult }> {
  return apiClient.get(`/payments/status?reference=${encodeURIComponent(reference)}`);
}

/**
 * Confirme le retour paiement carte.
 *
 * @param reference Numéro de commande
 * @param status success | cancel | decline
 */
export async function confirmCardReturn(
  reference: string,
  status: string,
): Promise<{ data: PaymentStatusResult }> {
  return apiClient.get(
    `/payments/card-return?reference=${encodeURIComponent(reference)}&status=${encodeURIComponent(status)}`,
  );
}
