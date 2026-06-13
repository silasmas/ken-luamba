"use client";

import { Suspense } from "react";
import ConnexionContent from "./ConnexionContent";

/**
 * Page de connexion avec boundary Suspense pour useSearchParams.
 */
export default function ConnexionPage() {
  return (
    <Suspense fallback={<p className="py-20 text-center text-stone-600">Chargement...</p>}>
      <ConnexionContent />
    </Suspense>
  );
}
