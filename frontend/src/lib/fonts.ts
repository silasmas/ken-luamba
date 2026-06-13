import { Inter, Cormorant_Garamond } from "next/font/google";

/**
 * Inter — texte courant et interface.
 */
export const inter = Inter({
  variable: "--font-sans-var",
  subsets: ["latin"],
  display: "swap",
});

/**
 * Cormorant — citations et éléments éditoriaux majeurs.
 */
export const cormorant = Cormorant_Garamond({
  variable: "--font-serif-var",
  weight: ["400", "500", "600"],
  subsets: ["latin"],
  display: "swap",
});
