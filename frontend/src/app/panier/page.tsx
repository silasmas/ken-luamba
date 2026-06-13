"use client";

import Link from "next/link";
import { useCartStore } from "@/stores/cartStore";
import { formatPrice } from "@/lib/formatPrice";

/**
 * Page panier avec gestion des quantités.
 */
export default function PanierPage() {
  const cart = useCartStore((state) => state.cart);
  const isLoading = useCartStore((state) => state.isLoading);
  const error = useCartStore((state) => state.error);
  const updateQuantity = useCartStore((state) => state.updateQuantity);
  const removeItem = useCartStore((state) => state.removeItem);

  if (isLoading && !cart) {
    return <p className="py-20 text-center text-stone-600">Chargement du panier...</p>;
  }

  if (!cart || cart.items.length === 0) {
    return (
      <div className="py-20 text-center">
        <h1 className="text-2xl font-bold text-stone-900">Votre panier est vide</h1>
        <p className="mt-3 text-stone-600">Découvrez les ouvrages du pasteur Ken Luamba.</p>
        <Link
          href="/livres"
          className="mt-6 inline-block rounded-lg bg-amber-600 px-6 py-3 text-sm font-semibold text-white"
        >
          Voir les livres
        </Link>
      </div>
    );
  }

  return (
    <div className="grid gap-8 lg:grid-cols-[2fr_1fr]">
      <section className="space-y-4">
        <h1 className="text-2xl font-bold text-stone-900">Panier</h1>
        {error && <p className="text-sm text-red-600">{error}</p>}
        {cart.summary.priceAlerts.map((alert) => (
          <p key={alert.itemId} className="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {alert.message}
          </p>
        ))}
        {cart.items.map((item) => (
          <article
            key={item.id}
            className="flex flex-col gap-4 rounded-xl border border-stone-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between"
          >
            <div>
              <p className="text-sm text-amber-700">{item.book.authorName}</p>
              <h2 className="font-semibold text-stone-900">{item.book.title}</h2>
              <p className="text-sm text-stone-500">
                {item.format.typeLabel} — {item.pricingPeriod?.label}
              </p>
            </div>
            <div className="flex items-center gap-4">
              <input
                type="number"
                min={1}
                max={99}
                value={item.quantity}
                onChange={(event) => updateQuantity(item.id, Number(event.target.value))}
                className="w-20 rounded-lg border border-stone-300 px-3 py-2"
              />
              <span className="min-w-24 text-right font-semibold">
                {formatPrice(item.lineTotal, cart.summary.currency)}
              </span>
              <button
                type="button"
                onClick={() => removeItem(item.id)}
                className="text-sm text-red-600 hover:underline"
              >
                Retirer
              </button>
            </div>
          </article>
        ))}
      </section>
      <aside className="h-fit rounded-xl border border-stone-200 bg-white p-6">
        <h2 className="text-lg font-semibold text-stone-900">Récapitulatif</h2>
        <dl className="mt-4 space-y-3 text-sm">
          <div className="flex justify-between">
            <dt className="text-stone-600">Sous-total</dt>
            <dd>{formatPrice(cart.summary.subtotal, cart.summary.currency)}</dd>
          </div>
          {cart.summary.discount.amount > 0 && (
            <div className="flex justify-between text-green-700">
              <dt>{cart.summary.discount.rule?.name}</dt>
              <dd>-{formatPrice(cart.summary.discount.amount, cart.summary.currency)}</dd>
            </div>
          )}
          <div className="flex justify-between border-t border-stone-200 pt-3 text-base font-semibold">
            <dt>Total</dt>
            <dd>{formatPrice(cart.summary.total, cart.summary.currency)}</dd>
          </div>
        </dl>
        <Link
          href="/checkout"
          className="mt-6 block w-full rounded-lg bg-amber-600 px-6 py-3 text-center text-sm font-semibold text-white hover:bg-amber-700"
        >
          Commander
        </Link>
      </aside>
    </div>
  );
}
