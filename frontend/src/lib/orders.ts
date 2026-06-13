import type { Order, OrderItem } from "@/types/order";

/**
 * Indique si la commande contient au moins un article physique.
 *
 * @param items Lignes de commande
 */
export function orderHasPhysicalItems(items: OrderItem[]): boolean {
  return items.some(
    (item) => item.formatType !== "ebook" && item.formatType !== "audiobook",
  );
}

/**
 * Indique si le QR code doit être affiché pour cette commande.
 *
 * @param order Commande client
 */
export function shouldShowOrderQr(order: Order): boolean {
  if (!order.qrToken || order.status === "pending_payment") {
    return false;
  }

  return orderHasPhysicalItems(order.items);
}
