"use client";

import { Suspense } from "react";
import CheckoutContent from "./CheckoutContent";
import { PageShell } from "@/components/layout/PageShell";

/**
 * Page checkout avec boundary Suspense pour useSearchParams.
 */
export default function CheckoutPage() {
  return (
    <Suspense
      fallback={
        <PageShell>
          <p className="py-20 text-center text-ink/60">Chargement du paiement...</p>
        </PageShell>
      }
    >
      <CheckoutContent />
    </Suspense>
  );
}
