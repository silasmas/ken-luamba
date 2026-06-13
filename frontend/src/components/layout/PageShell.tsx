/**
 * Conteneur centré pour les pages transactionnelles (panier, checkout, espace).
 */
export function PageShell({ children }: { children: React.ReactNode }) {
  return (
    <div className="mx-auto w-full max-w-7xl px-6 py-10 lg:px-10">
      {children}
    </div>
  );
}
