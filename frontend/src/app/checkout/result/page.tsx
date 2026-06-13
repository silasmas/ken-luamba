"use client";

import { Suspense } from "react";
import CheckoutResultContent from "./CheckoutResultContent";

/**
 * Page résultat paiement avec boundary Suspense.
 */
export default function CheckoutResultPage() {
  return (
    <Suspense fallback={<p className="py-20 text-center text-stone-600">Chargement...</p>}>
      <CheckoutResultContent />
    </Suspense>
  );
}
