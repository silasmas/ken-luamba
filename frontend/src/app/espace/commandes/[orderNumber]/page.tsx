"use client";

import Link from "next/link";
import { useParams, useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { EspaceLayout } from "@/components/espace/EspaceLayout";
import { fetchOrder, initiatePayment } from "@/lib/api/orders";
import { fetchMobileMoneyOperators } from "@/lib/api/payments";
import { OrderQrCode } from "@/components/orders/OrderQrCode";
import { CourierInfoCard } from "@/components/orders/CourierInfoCard";
import { OrderStatusBadge, isOrderDisputed } from "@/components/orders/OrderStatusBadge";
import { ConfirmDialog } from "@/components/ui/ConfirmDialog";
import { shouldShowOrderQr } from "@/lib/orders";
import {
  normalizeMobileMoneyPhone,
  validateMobileMoneyPhone,
  type MobileMoneyOperator,
  type PaymentStep,
} from "@/lib/mobileMoney";
import { formatPrice } from "@/lib/formatPrice";
import { useAuthStore } from "@/stores/authStore";
import type { Order } from "@/types/order";

const PAYMENT_POLL_INTERVAL_MS = 2000;

/**
 * Page détail commande avec paiement, confirmation réception et litige.
 */
export default function CommandeDetailPage() {
  const params = useParams();
  const router = useRouter();
  const orderNumber = params.orderNumber as string;
  const token = useAuthStore((state) => state.token);
  const isReady = useAuthStore((state) => state.isReady);
  const [order, setOrder] = useState<Order | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [channel, setChannel] = useState<"mobile_money" | "card">("mobile_money");
  const [paymentPhone, setPaymentPhone] = useState("");
  const [selectedProvider, setSelectedProvider] = useState("");
  const [mobileOperators, setMobileOperators] = useState<MobileMoneyOperator[]>([]);
  const [phoneValidationError, setPhoneValidationError] = useState<string | null>(null);
  const [paymentSteps, setPaymentSteps] = useState<PaymentStep[]>([]);
  const [paymentMessage, setPaymentMessage] = useState<string | null>(null);
  const [polling, setPolling] = useState(false);
  const [isPaying, setIsPaying] = useState(false);
  const [pendingAction, setPendingAction] = useState<"confirm" | "dispute" | null>(null);
  const [isActionLoading, setIsActionLoading] = useState(false);

  const selectedOperator = mobileOperators.find((op) => op.code === selectedProvider) ?? null;

  useEffect(() => {
    if (!isReady) {
      return;
    }

    if (!token) {
      router.replace(`/connexion?redirect=/espace/commandes/${orderNumber}`);
      return;
    }

    void fetchOrder(token, orderNumber).then(setOrder);
    void fetchMobileMoneyOperators()
      .then((operators) => {
        setMobileOperators(operators);
        if (operators.length > 0) {
          setSelectedProvider(operators[0].code);
        }
      })
      .catch(() => {});
  }, [token, isReady, orderNumber, router]);

  /**
   * Relance le paiement pour une commande en attente.
   */
  const handleRetryPayment = async () => {
    if (!token || !order) {
      return;
    }

    if (channel === "mobile_money") {
      const phoneError = validateMobileMoneyPhone(paymentPhone, selectedOperator);

      if (!selectedProvider || phoneError) {
        setError(phoneError ?? "Sélectionnez votre opérateur Mobile Money.");
        return;
      }
    }

    setIsPaying(true);
    setError(null);

    try {
      const payment = await initiatePayment(
        token,
        order.orderNumber,
        channel,
        channel === "mobile_money" ? paymentPhone : undefined,
        channel === "mobile_money" ? selectedProvider : undefined,
      );

      if (payment.data.type === "card" && payment.data.redirectUrl) {
        window.location.href = payment.data.redirectUrl;
        return;
      }

      setPaymentSteps(payment.data.steps ?? []);
      setPaymentMessage(payment.data.message ?? "Validez le paiement sur votre téléphone.");
      setPolling(true);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Erreur de paiement");
    } finally {
      setIsPaying(false);
    }
  };

  useEffect(() => {
    if (!polling || !order) {
      return;
    }

    let attempts = 0;

    const pollPayment = async () => {
      attempts += 1;

      try {
        const { checkPaymentStatus } = await import("@/lib/api/orders");
        const result = await checkPaymentStatus(order.orderNumber);

        if (result.data.steps) {
          setPaymentSteps(result.data.steps);
        }

        if (result.data.message) {
          setPaymentMessage(result.data.message);
        }

        if (result.data.status === 0 || (result.data.success && result.data.status !== 2)) {
          setPolling(false);
          router.push(`/checkout/result?reference=${order.orderNumber}&status=success`);
          return;
        }

        if (result.data.status === 1) {
          setPolling(false);
          setError(result.data.message);
        }
      } catch {
        // Continue le polling
      }

      if (attempts >= 45) {
        setPolling(false);
        setError("Délai dépassé. Vous pouvez réessayer le paiement.");
      }
    };

    void pollPayment();
    const interval = setInterval(() => {
      void pollPayment();
    }, PAYMENT_POLL_INTERVAL_MS);

    return () => clearInterval(interval);
  }, [polling, order, router]);

  /**
   * Confirme la réception de la commande après validation utilisateur.
   */
  const handleConfirm = async () => {
    if (!token) {
      return;
    }

    setIsActionLoading(true);
    setError(null);

    try {
      const { apiClient } = await import("@/lib/api/client");
      const response = await apiClient.post<{ data: Order; message: string }>(
        `/orders/${orderNumber}/confirm-receipt`,
        {},
        { token },
      );
      setOrder(response.data);
      setMessage(response.message);
      setPendingAction(null);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Erreur de confirmation");
    } finally {
      setIsActionLoading(false);
    }
  };

  /**
   * Ouvre un litige livraison après validation utilisateur.
   */
  const handleDispute = async () => {
    if (!token) {
      return;
    }

    setIsActionLoading(true);
    setError(null);

    try {
      const { apiClient } = await import("@/lib/api/client");
      const response = await apiClient.post<{ data: Order; message: string }>(
        `/orders/${orderNumber}/dispute-delivery`,
        { reason: "Je n'ai pas reçu ma commande." },
        { token },
      );
      setOrder(response.data);
      setMessage(response.message);
      setPendingAction(null);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Erreur lors du litige");
    } finally {
      setIsActionLoading(false);
    }
  };

  if (!isReady || !order) {
    return (
      <EspaceLayout>
        <p className="py-10 text-center text-stone-600">Chargement...</p>
      </EspaceLayout>
    );
  }

  return (
    <EspaceLayout>
      <div className="mx-auto max-w-2xl space-y-6">
        <Link href="/espace/commandes" className="text-sm text-amber-700 hover:underline">
          ← Retour
        </Link>
        <h1 className="text-2xl font-bold">{order.orderNumber}</h1>
        <div className="mt-2">
          <OrderStatusBadge status={order.status} label={order.statusLabel} />
        </div>
        {isOrderDisputed(order) && (
          <p className="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
            Litige en cours : vous avez signalé ne pas avoir reçu cette commande. Notre équipe examine votre dossier.
          </p>
        )}
        {order.courier && (
          <CourierInfoCard courier={order.courier} />
        )}
        {message && <p className="rounded-lg bg-green-50 p-3 text-sm text-green-800">{message}</p>}
        {error && <p className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}

        <section className="rounded-xl border border-stone-200 bg-white p-5">
          <h2 className="font-semibold">Articles</h2>
          <ul className="mt-3 space-y-2 text-sm">
            {order.items.map((item) => (
              <li key={item.id} className="flex justify-between">
                <span>
                  {item.bookTitle} ({item.formatLabel}) × {item.quantity}
                </span>
                <span>{formatPrice(item.totalPrice, order.currency)}</span>
              </li>
            ))}
          </ul>
          <p className="mt-4 font-semibold">Total : {formatPrice(order.total, order.currency)}</p>
        </section>

        {order.status === "pending_payment" && (
          <section className="rounded-xl border border-amber-200 bg-amber-50 p-5">
            <h2 className="font-semibold text-stone-900">Reprendre le paiement</h2>
            <p className="mt-1 text-sm text-stone-600">
              Votre commande est enregistrée. Choisissez un moyen de paiement et réessayez.
            </p>

            <div className="mt-4 flex gap-3">
              <button
                type="button"
                onClick={() => setChannel("mobile_money")}
                className={`rounded-lg px-4 py-2 text-sm ${
                  channel === "mobile_money" ? "bg-amber-600 text-white" : "bg-white"
                }`}
              >
                Mobile Money
              </button>
              <button
                type="button"
                onClick={() => setChannel("card")}
                className={`rounded-lg px-4 py-2 text-sm ${
                  channel === "card" ? "bg-amber-600 text-white" : "bg-white"
                }`}
              >
                Carte bancaire
              </button>
            </div>

            {channel === "mobile_money" && (
              <div className="mt-4 space-y-3">
                <div className="grid gap-2 sm:grid-cols-2">
                  {mobileOperators.map((operator) => (
                    <label
                      key={operator.code}
                      className={`flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm ${
                        selectedProvider === operator.code
                          ? "border-amber-600 bg-white"
                          : "border-stone-200 bg-white"
                      }`}
                    >
                      <input
                        type="radio"
                        checked={selectedProvider === operator.code}
                        onChange={() => setSelectedProvider(operator.code)}
                      />
                      {operator.label}
                    </label>
                  ))}
                </div>
                <input
                  placeholder={selectedOperator?.phoneHint ?? "243XXXXXXXXX"}
                  value={paymentPhone}
                  onChange={(event) => {
                    const normalized = normalizeMobileMoneyPhone(event.target.value);
                    setPaymentPhone(normalized);
                    setPhoneValidationError(
                      validateMobileMoneyPhone(normalized, selectedOperator),
                    );
                  }}
                  className="w-full rounded-lg border border-stone-300 px-4 py-2"
                />
                {phoneValidationError && (
                  <p className="text-sm text-red-600">{phoneValidationError}</p>
                )}
              </div>
            )}

            <button
              type="button"
              disabled={isPaying || polling || Boolean(phoneValidationError)}
              onClick={() => void handleRetryPayment()}
              className="mt-4 rounded-lg bg-amber-600 px-6 py-2 text-sm font-semibold text-white disabled:opacity-60"
            >
              {isPaying ? "Traitement..." : polling ? "Paiement en cours..." : "Payer maintenant"}
            </button>

            {(paymentSteps.length > 0 || paymentMessage) && (
              <div className="mt-4">
                <PaymentSteps steps={paymentSteps} message={paymentMessage} />
              </div>
            )}
          </section>
        )}

        {order.status === "delivered_by_courier" && (
          <div className="flex gap-3">
            <button
              type="button"
              onClick={() => setPendingAction("confirm")}
              className="rounded-lg bg-green-600 px-5 py-2 text-sm font-semibold text-white"
            >
              Confirmer la réception
            </button>
            <button
              type="button"
              onClick={() => setPendingAction("dispute")}
              className="rounded-lg border border-red-300 px-5 py-2 text-sm text-red-700"
            >
              Contester
            </button>
          </div>
        )}

        <ConfirmDialog
          isOpen={pendingAction === "confirm"}
          title="Confirmer la réception"
          message="Confirmez-vous avoir bien reçu votre commande en bon état ? Cette action est définitive."
          confirmLabel="Oui, j'ai bien reçu"
          isLoading={isActionLoading}
          onCancel={() => setPendingAction(null)}
          onConfirm={() => void handleConfirm()}
        />
        <ConfirmDialog
          isOpen={pendingAction === "dispute"}
          title="Contester la livraison"
          message="Êtes-vous sûr de ne pas avoir reçu votre commande ? Un litige sera ouvert et le livreur sera notifié."
          confirmLabel="Oui, je n'ai pas reçu"
          variant="danger"
          isLoading={isActionLoading}
          onCancel={() => setPendingAction(null)}
          onConfirm={() => void handleDispute()}
        />

        {shouldShowOrderQr(order) && (
          <OrderQrCode token={order.qrToken!} orderNumber={order.orderNumber} />
        )}
      </div>
    </EspaceLayout>
  );
}
