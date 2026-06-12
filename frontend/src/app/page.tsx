import Link from "next/link";

export default function Home() {
  return (
    <div className="min-h-screen bg-stone-50 text-stone-900">
      <header className="border-b border-stone-200 bg-white">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
          <span className="text-lg font-semibold tracking-tight">
            Ken Luamba
          </span>
          <nav className="flex gap-6 text-sm font-medium text-stone-600">
            <Link href="/livres" className="hover:text-stone-900">
              Livres
            </Link>
            <Link href="/panier" className="hover:text-stone-900">
              Panier
            </Link>
            <Link href="/connexion" className="hover:text-stone-900">
              Connexion
            </Link>
          </nav>
        </div>
      </header>

      <main className="mx-auto max-w-6xl px-6 py-20">
        <section className="max-w-2xl">
          <p className="mb-4 text-sm font-medium uppercase tracking-widest text-amber-700">
            Site officiel
          </p>
          <h1 className="mb-6 text-4xl font-bold leading-tight tracking-tight md:text-5xl">
            Pré-commandez et achetez les ouvrages de Ken Luamba
          </h1>
          <p className="mb-10 text-lg leading-relaxed text-stone-600">
            Formats relié, ebook et audio. Livraison à domicile ou retrait sur
            place avec QR code. Espace membre sécurisé pour accéder à vos
            achats.
          </p>
          <div className="flex flex-wrap gap-4">
            <Link
              href="/livres"
              className="rounded-lg bg-amber-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-amber-700"
            >
              Voir les livres
            </Link>
            <Link
              href="/connexion"
              className="rounded-lg border border-stone-300 bg-white px-6 py-3 text-sm font-semibold text-stone-800 transition hover:border-stone-400"
            >
              Mon espace membre
            </Link>
          </div>
        </section>
      </main>

      <footer className="border-t border-stone-200 bg-white">
        <div className="mx-auto max-w-6xl px-6 py-8 text-sm text-stone-500">
          © {new Date().getFullYear()} Ken Luamba. Tous droits réservés.
        </div>
      </footer>
    </div>
  );
}
