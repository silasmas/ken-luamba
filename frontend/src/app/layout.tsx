import type { Metadata } from "next";
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
      <body className="antialiased">{children}</body>
    </html>
  );
}
