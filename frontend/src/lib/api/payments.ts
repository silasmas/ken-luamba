import { apiClient } from "@/lib/api/client";
import type { MobileMoneyOperator } from "@/lib/mobileMoney";

/**
 * Liste les opérateurs Mobile Money disponibles.
 */
export async function fetchMobileMoneyOperators(): Promise<MobileMoneyOperator[]> {
  const response = await apiClient.get<{ data: MobileMoneyOperator[] }>(
    "/payments/mobile-providers",
  );

  return response.data;
}
