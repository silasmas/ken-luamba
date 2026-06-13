"use client";

import Link from "next/link";
import { useEffect, useMemo, useState } from "react";
import { EspaceLayout } from "@/components/espace/EspaceLayout";
import { OrderStatusBadge, isOrderDisputed } from "@/components/orders/OrderStatusBadge";
import { fetchOrders } from "@/lib/api/orders";
import { formatPrice } from "@/lib/formatPrice";
import { useAuthStore } from "@/stores/authStore";
import type { Order } from "@/types/order";

type OrderFilter =
  | "all"
  | "pending_payment"
  | "active"
  | "delivered"
  | "completed"
  | "failed";

const FILTER_LABELS: Record<OrderFilter, string> = {
  all: "Toutes",
  pending_payment: "En attente de paiement",
  active: "En cours",
  delivered: "À confirmer",
  completed: "Terminées",
  failed: "Échouées",
};

const FILTER_KEYS = Object.keys(FILTER_LABELS) as OrderFilter[];

/**
 * Calcule le nombre de commandes par filtre.
 *
 * @param orders Liste des commandes
 * @returns Compteurs par filtre
 */
function countByFilter(orders: Order[]): Record<OrderFilter, number> {
  return FILTER_KEYS.reduce(
    (counts, key) => {
      counts[key] = orders.filter((order) => matchesFilter(order, key)).length;
      return counts;
    },
    {} as Record<OrderFilter, number>,
  );
}

/**
 * Détermine si une commande correspond au filtre sélectionné.
 *
 * @param order Commande
 * @param filter Filtre actif
 */
function matchesFilter(order: Order, filter: OrderFilter): boolean {
  if (filter === "all") {
    return true;
  }

  if (filter === "pending_payment") {
    return order.status === "pending_payment";
  }

  if (filter === "active") {
    return ["paid", "processing", "out_for_delivery"].includes(order.status);
  }

  if (filter === "delivered") {
    return order.status === "delivered_by_courier";
  }

  if (filter === "completed") {
    return order.status === "completed";
  }

  return ["cancelled", "refunded", "delivery_disputed"].includes(order.status)
    || (order.status === "pending_payment" && order.payment?.status === "failed");
}

/**
 * Page espace membre — liste des commandes avec filtres.
 */
export default function MesCommandesPage() {
  const token = useAuthStore((state) => state.token);
  const isReady = useAuthStore((state) => state.isReady);
  const [orders, setOrders] = useState<Order[]>([]);
  const [filter, setFilter] = useState<OrderFilter>("all");
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!isReady) {
      return;
    }

    if (!token) {
      setIsLoading(false);
      return;
    }

    void fetchOrders(token)
      .then((data) => {
        setOrders(data);
        setError(null);
      })
      .catch((err) => {
        setError(err instanceof Error ? err.message : "Impossible de charger les commandes.");
      })
      .finally(() => setIsLoading(false));
  }, [token, isReady]);

  const filteredOrders = useMemo(
    () => orders.filter((order) => matchesFilter(order, filter)),
    [orders, filter],
  );

  const filterCounts = useMemo(() => countByFilter(orders), [orders]);

  if (!isReady || isLoading) {
    return (
      <EspaceLayout>
        <p className="py-10 text-center text-stone-600">Chargement...</p>
      </EspaceLayout>
    );
  }

  if (!token) {
    return (
      <EspaceLayout>
        <div className="py-10 text-center">
          <p className="text-stone-600">Connectez-vous pour voir vos commandes.</p>
          <Link
            href="/connexion?redirect=/espace/commandes"
            className="mt-4 inline-block text-amber-700 hover:underline"
          >
            Se connecter
          </Link>
        </div>
      </EspaceLayout>
    );
  }

  return (
    <EspaceLayout>
      <div className="space-y-6">
        <h1 className="text-2xl font-bold text-stone-900">Mes commandes</h1>

        <div className="flex flex-wrap gap-2">
          {(Object.keys(FILTER_LABELS) as OrderFilter[]).map((key) => (
            <button
              key={key}
              type="button"
              onClick={() => setFilter(key)}
              className={`inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm ${
                filter === key ? "bg-amber-600 text-white" : "bg-stone-100 text-stone-700"
              }`}
            >
              {FILTER_LABELS[key]}
              <span
                className={`rounded-full px-2 py-0.5 text-xs font-semibold ${
                  filter === key ? "bg-white/20 text-white" : "bg-stone-200 text-stone-700"
                }`}
              >
                {filterCounts[key]}
              </span>
            </button>
          ))}
        </div>

        {error && <p className="text-sm text-red-600">{error}</p>}

        {filteredOrders.length === 0 ? (
          <p className="text-stone-600">Aucune commande dans cette catégorie.</p>
        ) : (
          filteredOrders.map((order) => (
            <article
              key={order.id}
              className={`rounded-xl border bg-white p-5 ${
                isOrderDisputed(order) ? "border-red-300 ring-1 ring-red-200" : "border-stone-200"
              }`}
            >
              <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                  <p className="font-semibold text-stone-900">{order.orderNumber}</p>
                  <div className="mt-1">
                    <OrderStatusBadge status={order.status} label={order.statusLabel} />
                  </div>
                  {isOrderDisputed(order) && (
                    <p className="mt-2 text-xs font-medium text-red-700">Litige en cours</p>
                  )}
                  {order.createdAt && (
                    <p className="text-xs text-stone-400">
                      {new Date(order.createdAt).toLocaleDateString("fr-FR")}
                    </p>
                  )}
                </div>
                <p className="font-semibold">{formatPrice(order.total, order.currency)}</p>
              </div>

              <div className="mt-3 flex flex-wrap gap-3">
                <Link
                  href={`/espace/commandes/${order.orderNumber}`}
                  className="text-sm text-amber-700 hover:underline"
                >
                  Voir le détail
                </Link>
                {order.status === "pending_payment" && (
                  <Link
                    href={`/checkout?order=${order.orderNumber}`}
                    className="text-sm font-medium text-amber-700 hover:underline"
                  >
                    Reprendre le paiement
                  </Link>
                )}
              </div>
            </article>
          ))
        )}
      </div>
    </EspaceLayout>
  );
}
