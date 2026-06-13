"use client";

import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { useEffect, useState } from "react";
import { confirmCardReturn, checkPaymentStatus } from "@/lib/api/orders";
import { OrderQrCode } from "@/components/orders/OrderQrCode";
import { useCartStore } from "@/stores/cartStore";
import { formatPrice } from "@/lib/formatPrice";

/**
 * Contenu de la page résultat après paiement.
 */
export default function CheckoutResultContent() {
  const searchParams = useSearchParams();
  const reference = searchParams.get("reference") ?? "";
  const status = searchParams.get("status") ?? "success";
  const amount = searchParams.get("amount");
  const currency = searchParams.get("currency") ?? "CDF";

  const [message, setMessage] = useState("Traitement du paiement...");
  const [qrToken, setQrToken] = useState<string | null>(null);
  const [isSuccess, setIsSuccess] = useState(false);

  const initCart = useCartStore((state) => state.initCart);

  useEffect(() => {
    if (isSuccess) {
      void initCart();
    }
  }, [isSuccess, initCart]);

  useEffect(() => {
    if (!reference) {
      setMessage("Référence de commande manquante.");
      return;
    }

    if (status === "success") {
      void checkPaymentStatus(reference)
        .then((result) => {
          setIsSuccess(true);
          setMessage(result.data.message || "Paiement confirmé. Merci pour votre commande !");
          setQrToken(result.data.qrToken ?? null);
        })
        .catch(() => {
          setIsSuccess(true);
          setMessage("Paiement confirmé. Merci pour votre commande !");
        });
      return;
    }

    void confirmCardReturn(reference, status)
      .then((result) => {
        setIsSuccess(result.data.success);
        setMessage(result.data.message);
        setQrToken(result.data.qrToken ?? null);
      })
      .catch(() => {
        if (status === "success") {
          setIsSuccess(true);
          setMessage("Paiement confirmé. Merci pour votre commande !");
        } else {
          setMessage(status === "decline" ? "Paiement refusé." : "Paiement annulé.");
        }
      });
  }, [reference, status]);

  return (
    <div className="mx-auto max-w-lg text-center">
      <h1 className="text-2xl font-bold text-stone-900">
        {isSuccess ? "Commande confirmée" : "Paiement non abouti"}
      </h1>
      <p className="mt-4 text-stone-600">{message}</p>

      {reference && (
        <p className="mt-2 text-sm text-stone-500">
          Référence : <strong>{reference}</strong>
        </p>
      )}

      {amount && (
        <p className="mt-1 text-sm text-stone-500">
          Montant : {formatPrice(Number(amount), currency)}
        </p>
      )}

      {qrToken && reference && (
        <div className="mt-6">
          <OrderQrCode token={qrToken} orderNumber={reference} />
        </div>
      )}

      <div className="mt-8 flex flex-wrap justify-center gap-4">
        <Link
          href="/livres"
          className="rounded-lg bg-amber-600 px-6 py-3 text-sm font-semibold text-white"
        >
          Continuer mes achats
        </Link>
        {!isSuccess && reference && (
          <Link
            href={`/checkout?order=${reference}`}
            className="rounded-lg border border-amber-600 px-6 py-3 text-sm font-semibold text-amber-700"
          >
            Réessayer le paiement
          </Link>
        )}
        <Link href="/espace/commandes" className="rounded-lg border border-stone-300 px-6 py-3 text-sm">
          Mes commandes
        </Link>
      </div>
    </div>
  );
}
