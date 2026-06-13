const API_BASE_URL =
  process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8001/api/v1";

export interface CourierDelivery {
  id: string;
  status: string;
  statusLabel: string;
  assignedAt?: string | null;
  deliveredAt?: string | null;
  orderNumber?: string | null;
  orderStatus?: string | null;
  orderStatusLabel?: string | null;
  customerName?: string | null;
  customerPhone?: string | null;
  fulfillmentType?: string | null;
  fulfillmentLabel?: string | null;
  shippingAddress?: Record<string, string> | null;
  pickupPoint?: {
    name: string;
    address: string;
    city: string;
    phone?: string | null;
  } | null;
  items: Array<{
    bookTitle: string;
    formatLabel: string;
    quantity: number;
  }>;
  notes?: string | null;
}

export interface CourierScanResult {
  orderId: string;
  orderNumber: string;
  status: string;
  statusLabel: string;
  customerName?: string | null;
  fulfillmentType?: string | null;
  fulfillmentLabel?: string | null;
  shippingAddress?: Record<string, string> | null;
  pickupPoint?: CourierDelivery["pickupPoint"];
  items: CourierDelivery["items"];
  deliveryId?: string | null;
  qrUsed: boolean;
}

/**
 * Récupère les courses du livreur et les livraisons disponibles.
 *
 * @param token Token Sanctum livreur
 */
export async function fetchCourierDeliveries(token: string): Promise<{
  mine: CourierDelivery[];
  available: CourierDelivery[];
}> {
  const response = await fetch(`${API_BASE_URL}/courier/deliveries`, {
    headers: {
      Accept: "application/json",
      Authorization: `Bearer ${token}`,
    },
  });

  if (!response.ok) {
    throw new Error("Impossible de charger les courses.");
  }

  const body = (await response.json()) as {
    data: { mine: CourierDelivery[]; available: CourierDelivery[] };
  };

  return body.data;
}

/**
 * Prend en charge une livraison disponible.
 *
 * @param token Token livreur
 * @param deliveryId Identifiant livraison
 */
export async function acceptCourierDelivery(
  token: string,
  deliveryId: string,
): Promise<{ message: string; data: CourierDelivery }> {
  const response = await fetch(`${API_BASE_URL}/courier/deliveries/${deliveryId}/accept`, {
    method: "POST",
    headers: {
      Accept: "application/json",
      Authorization: `Bearer ${token}`,
    },
  });

  if (!response.ok) {
    const body = (await response.json().catch(() => ({}))) as { message?: string };
    throw new Error(body.message ?? "Prise en charge impossible.");
  }

  return response.json() as Promise<{ message: string; data: CourierDelivery }>;
}

/**
 * Scanne un token QR et retourne les détails commande.
 *
 * @param token Token livreur
 * @param qrToken Jeton QR client
 */
export async function scanCourierQr(
  token: string,
  qrToken: string,
): Promise<CourierScanResult> {
  const response = await fetch(`${API_BASE_URL}/courier/scan`, {
    method: "POST",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify({ token: qrToken }),
  });

  if (!response.ok) {
    const body = (await response.json().catch(() => ({}))) as { message?: string };
    throw new Error(body.message ?? "QR code invalide.");
  }

  const body = (await response.json()) as { data: CourierScanResult };

  return body.data;
}

/**
 * Confirme une livraison avec photo preuve optionnelle.
 *
 * @param token Token livreur
 * @param qrToken Jeton QR
 * @param photo Photo preuve
 * @param comment Commentaire livreur
 */
export async function confirmCourierDelivery(
  token: string,
  qrToken: string,
  photo?: File | null,
  comment?: string,
): Promise<{ message: string }> {
  const formData = new FormData();
  formData.append("token", qrToken);

  if (comment) {
    formData.append("comment", comment);
  }

  if (photo) {
    formData.append("photo", photo);
  }

  const response = await fetch(`${API_BASE_URL}/courier/confirm`, {
    method: "POST",
    headers: {
      Accept: "application/json",
      Authorization: `Bearer ${token}`,
    },
    body: formData,
  });

  if (!response.ok) {
    const body = (await response.json().catch(() => ({}))) as { message?: string };
    throw new Error(body.message ?? "Confirmation impossible.");
  }

  return response.json() as Promise<{ message: string }>;
}
