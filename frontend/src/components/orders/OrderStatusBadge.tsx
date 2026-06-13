import type { Order } from "@/types/order";

interface OrderStatusBadgeProps {
  /** Statut technique de la commande */
  status: string;
  /** Libellé affiché */
  label: string;
}

/**
 * Styles visuels par statut de commande.
 */
const STATUS_STYLES: Record<string, string> = {
  pending_payment: "bg-amber-100 text-amber-800 ring-amber-200",
  paid: "bg-blue-100 text-blue-800 ring-blue-200",
  processing: "bg-blue-100 text-blue-800 ring-blue-200",
  out_for_delivery: "bg-sky-100 text-sky-800 ring-sky-200",
  delivered_by_courier: "bg-orange-100 text-orange-800 ring-orange-200",
  completed: "bg-green-100 text-green-800 ring-green-200",
  delivery_disputed: "bg-red-100 text-red-800 ring-red-300",
  cancelled: "bg-stone-100 text-stone-600 ring-stone-200",
  refunded: "bg-stone-100 text-stone-600 ring-stone-200",
};

/**
 * Badge coloré affichant le statut d'une commande.
 */
export function OrderStatusBadge({ status, label }: OrderStatusBadgeProps) {
  const style = STATUS_STYLES[status] ?? "bg-stone-100 text-stone-700 ring-stone-200";

  return (
    <span
      className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ${style}`}
    >
      {label}
    </span>
  );
}

/**
 * Indique si une commande est en litige livraison.
 *
 * @param order Commande à vérifier
 * @returns True si statut litige
 */
export function isOrderDisputed(order: Pick<Order, "status">): boolean {
  return order.status === "delivery_disputed";
}
