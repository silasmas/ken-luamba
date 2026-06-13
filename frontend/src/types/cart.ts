/**
 * Types pour le panier e-commerce.
 */
export interface CartItem {
  id: string;
  quantity: number;
  unitPrice: string;
  lineTotal: number;
  book: {
    id: string;
    title: string;
    slug: string;
    coverImage?: string | null;
    authorName?: string;
  };
  format: {
    id: string;
    type: string;
    typeLabel: string;
    sku: string;
    isDigital: boolean;
  };
  pricingPeriod?: {
    id: string;
    label: string;
    type: string;
  };
}

export interface CartDiscountRule {
  id: string;
  name: string;
  minQuantity: number;
  discountType: string;
  discountValue: string;
}

export interface CartSummary {
  itemCount: number;
  subtotal: number;
  discount: {
    rule: CartDiscountRule | null;
    amount: number;
  };
  total: number;
  currency: string;
  priceAlerts: Array<{
    itemId: string;
    message: string;
    oldPrice?: string;
    newPrice?: string;
  }>;
}

export interface Cart {
  id: string;
  sessionId?: string | null;
  items: CartItem[];
  summary: CartSummary;
}

export interface CartSessionResponse {
  sessionId: string;
}
