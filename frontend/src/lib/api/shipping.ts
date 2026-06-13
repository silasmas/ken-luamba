import { apiClient } from "@/lib/api/client";

export interface ShippingZoneCommune {
  id: string;
  name: string;
  city?: string | null;
}

export interface ShippingCity {
  id: string;
  name: string;
  isDeliveryAvailable: boolean;
}

export interface ShippingZone {
  id: string;
  name: string;
  amount: number;
  currency: string;
  cityId?: string | null;
  cityName?: string | null;
  communes: ShippingZoneCommune[];
}

export interface ShippingConfig {
  isActive: boolean;
  pricingMode: "fixed" | "zone";
  pricingModeLabel: string;
  fixedAmount: number;
  currency: string;
  domesticCountryCode: string;
  domesticCountryName: string;
  internationalPolicy: "fixed" | "quote" | "unavailable";
  internationalPolicyLabel: string;
  internationalAmount?: number | null;
  internationalMessage?: string | null;
  cities: ShippingCity[];
  zones: ShippingZone[];
}

export interface ShippingQuote {
  amount: number;
  currency: string;
  label: string;
  isInternational: boolean;
  requiresQuote: boolean;
  zoneId?: string | null;
  zoneName?: string | null;
  policyMessage?: string | null;
}

/**
 * Récupère la configuration publique des frais de livraison.
 */
export async function fetchShippingConfig(): Promise<ShippingConfig> {
  const response = await apiClient.get<{ data: ShippingConfig }>("/shipping/config");

  return response.data;
}

/**
 * Calcule un devis de livraison pour une adresse.
 *
 * @param payload Paramètres de livraison
 */
export async function fetchShippingQuote(payload: {
  fulfillmentType: "delivery" | "pickup";
  country?: string;
  city?: string;
  commune?: string;
}): Promise<ShippingQuote> {
  const response = await apiClient.post<{ data: ShippingQuote }>("/shipping/quote", payload);

  return response.data;
}
