import type { Metadata } from "next";
import { CartInitializer } from "@/components/cart/CartInitializer";
import { AuthInitializer } from "@/components/auth/AuthInitializer";
import { Header } from "@/components/layout/Header";
import "./globals.css";

export const metadata: Metadata = {
  title: "Ken Luamba — Livres & Pré-commandes",
  description:
    "Plateforme officielle de pré-commande et vente des ouvrages du pasteur Ken Luamba.",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="fr">
      <body className="min-h-screen bg-stone-50 text-stone-900 antialiased">
        <CartInitializer />
        <AuthInitializer />
        <Header />
        <main className="mx-auto max-w-6xl px-6 py-10">{children}</main>
        <footer className="border-t border-stone-200 bg-white">
          <div className="mx-auto max-w-6xl px-6 py-8 text-sm text-stone-500">
            © {new Date().getFullYear()} Ken Luamba. Tous droits réservés.
          </div>
        </footer>
      </body>
    </html>
  );
}
