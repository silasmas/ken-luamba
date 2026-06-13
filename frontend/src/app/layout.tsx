import type { Metadata } from "next";
import { CartInitializer } from "@/components/cart/CartInitializer";
import { AuthInitializer } from "@/components/auth/AuthInitializer";
import { WishlistInitializer } from "@/components/wishlist/WishlistInitializer";
import { SiteHeader } from "@/components/layout/SiteHeader";
import { SiteFooter } from "@/components/layout/SiteFooter";
import { inter, cormorant } from "@/lib/fonts";
import "./globals.css";

export const metadata: Metadata = {
  title: {
    default: "Ken Luamba — Éditions & Enseignements",
    template: "%s · Ken Luamba",
  },
  description:
    "Plateforme officielle de pré-commande et vente des ouvrages du pasteur Ken Luamba.",
};

/**
 * Layout racine — design book-site + logique boutique existante.
 */
export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html
      lang="fr"
      className={`${inter.variable} ${cormorant.variable} h-full antialiased`}
    >
      <body className="bg-paper text-ink min-h-full flex flex-col">
        <CartInitializer />
        <AuthInitializer />
        <WishlistInitializer />
        <SiteHeader />
        <main className="flex-1">{children}</main>
        <SiteFooter />
      </body>
    </html>
  );
}
