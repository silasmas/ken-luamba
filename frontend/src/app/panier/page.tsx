"use client";

import Link from "next/link";
import { useEffect } from "react";
import { Button } from "@/components/ui/button";
import { PageShell } from "@/components/layout/PageShell";
import { useCartStore } from "@/stores/cartStore";
import { formatPrice } from "@/lib/formatPrice";

/**
 * Page panier avec gestion des quantités.
 */
export default function PanierPage() {
  const cart = useCartStore((state) => state.cart);
  const isInitializing = useCartStore((state) => state.isInitializing);
  const isMutating = useCartStore((state) => state.isMutating);
  const error = useCartStore((state) => state.error);
  const initCart = useCartStore((state) => state.initCart);
  const updateQuantity = useCartStore((state) => state.updateQuantity);
  const removeItem = useCartStore((state) => state.removeItem);

  useEffect(() => {
    void initCart();
  }, [initCart]);

  if (isInitializing && !cart) {
    return (
      <PageShell>
        <p className="py-20 text-center text-ink/60">Chargement du panier...</p>
      </PageShell>
    );
  }

  if (!cart || cart.items.length === 0) {
    return (
      <PageShell>
        <div className="py-20 text-center">
          <h1 className="font-display text-2xl text-ink">Votre panier est vide</h1>
          <p className="mt-3 text-ink/60">Découvrez les ouvrages du pasteur Ken Luamba.</p>
          <Button asChild variant="primary" size="lg" className="mt-6">
            <Link href="/livres">Voir les livres</Link>
          </Button>
        </div>
      </PageShell>
    );
  }

  return (
    <PageShell>
    <div className="grid gap-8 lg:grid-cols-[2fr_1fr]">
      <section className="space-y-4">
        <h1 className="font-display text-2xl text-ink">Panier</h1>
        {error && <p className="text-sm text-red-600">{error}</p>}
        {cart.summary.priceAlerts.map((alert) => (
          <p key={alert.itemId} className="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {alert.message}
            {alert.oldPrice && alert.newPrice && (
              <span className="mt-1 block font-medium">
                {formatPrice(Number(alert.oldPrice), cart.summary.currency)} →{" "}
                {formatPrice(Number(alert.newPrice), cart.summary.currency)}
              </span>
            )}
          </p>
        ))}
        {cart.items.map((item) => (
          <article
            key={item.id}
            className="flex flex-col gap-4 rounded-2xl border border-ink/[0.08] bg-white/60 p-5 sm:flex-row sm:items-center sm:justify-between"
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
      <aside className="h-fit rounded-2xl border border-ink/[0.08] bg-white/60 p-6">
        <h2 className="font-display text-lg text-ink">Récapitulatif</h2>
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
        <Button asChild variant="primary" size="lg" className="mt-6 w-full">
          <Link href="/checkout">Commander</Link>
        </Button>
      </aside>
    </div>
    </PageShell>
  );
}
