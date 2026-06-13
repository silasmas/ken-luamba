/**
 * Types pour les commandes et paiements.
 */
export interface PickupPoint {
  id: string;
  name: string;
  address: string;
  city: string;
  phone?: string | null;
  openingHours?: string | null;
}

export interface OrderCourier {
  id: string;
  fullName: string;
  phone?: string | null;
  avatarUrl?: string | null;
}

export interface OrderItem {
  id: string;
  bookTitle: string;
  formatType: string;
  formatLabel: string;
  quantity: number;
  unitPrice: number;
  totalPrice: number;
}

export interface Order {
  id: string;
  orderNumber: string;
  status: string;
  statusLabel: string;
  fulfillmentType?: string | null;
  fulfillmentLabel?: string | null;
  pickupPoint?: PickupPoint | null;
  shippingAddress?: Record<string, string> | null;
  subtotal: number;
  discountAmount: number;
  shippingAmount: number;
  total: number;
  currency: string;
  notes?: string | null;
  paidAt?: string | null;
  items: OrderItem[];
  payment?: {
    id: string;
    status: string;
    statusLabel: string;
    channel?: string | null;
    channelLabel?: string | null;
    providerReference?: string | null;
  };
  qrToken?: string | null;
  courier?: OrderCourier | null;
  createdAt?: string | null;
}

export interface PaymentInitResult {
  type: "mobile_money" | "card";
  message?: string;
  redirectUrl?: string;
  orderNumber?: string | null;
  reference: string;
  operatorLabel?: string;
  providerCode?: string;
  phone?: string;
  steps?: PaymentStep[];
}

export interface PaymentStep {
  id: string;
  label: string;
  status: "pending" | "active" | "done" | "error";
}

export interface PaymentStatusResult {
  success: boolean;
  message: string;
  status?: number;
  orderNumber?: string | null;
  orderId?: string;
  qrToken?: string | null;
  steps?: PaymentStep[];
}
