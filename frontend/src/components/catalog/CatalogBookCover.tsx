"use client";

import Image from "next/image";
import { cn } from "@/lib/utils";
import { resolveMediaUrl } from "@/lib/resolveMediaUrl";
import { accentForSlug } from "@/lib/catalogUi";
import type { BookSummary } from "@/types/catalog";

/**
 * Couverture 3D pour un livre issu de l'API catalogue.
 */
export function CatalogBookCover({
  book,
  className,
  interactive = true,
  width = 220,
}: {
  book: BookSummary;
  className?: string;
  interactive?: boolean;
  width?: number;
}) {
  const height = Math.round(width * 1.52);
  const spine = Math.max(18, Math.round(width * 0.11));
  const accent = book.accentColor ?? accentForSlug(book.slug);
  const coverSrc = resolveMediaUrl(book.coverImage);

  return (
    <div
      className={cn("book-scene group/book select-none", className)}
      style={{ width, height }}
    >
      <div
        className={cn(
          "book-3d relative h-full w-full",
          interactive &&
            "[transform:rotateY(-22deg)_rotateX(4deg)] group-hover/book:[transform:rotateY(-8deg)_rotateX(2deg)_translateY(-10px)]",
        )}
        style={{
          filter:
            "drop-shadow(0 30px 45px rgba(10,10,10,0.28)) drop-shadow(0 8px 14px rgba(10,10,10,0.18))",
        }}
      >
        <div
          className="absolute left-0 top-0 h-full origin-left"
          style={{
            width: spine,
            transform: `translateX(-${spine - 1}px) rotateY(90deg)`,
            transformOrigin: "right center",
            background: `linear-gradient(90deg, ${shade(accent, -28)}, ${shade(accent, -8)})`,
          }}
        />

        <div className="absolute inset-0 overflow-hidden rounded-[3px] rounded-l-[2px] ring-1 ring-black/10">
          {coverSrc ? (
            <Image
              src={coverSrc}
              alt={`Couverture — ${book.title}`}
              fill
              sizes={`${width}px`}
              className="object-cover"
            />
          ) : (
            <div
              className="flex h-full w-full flex-col justify-between p-4 text-white"
              style={{
                background: `radial-gradient(120% 80% at 20% 0%, ${shade(accent, 18)} 0%, ${accent} 45%, ${shade(accent, -22)} 100%)`,
              }}
            >
              <span className="text-[0.55rem] uppercase tracking-[0.3em] text-white/55">
                Éditions KL
              </span>
              <h3 className="font-display text-lg leading-tight">{book.title}</h3>
              <span className="text-[0.6rem] uppercase tracking-[0.28em] text-white/75">
                Ken Luamba
              </span>
            </div>
          )}
          <div className="pointer-events-none absolute inset-y-0 left-0 w-[14%] bg-gradient-to-r from-black/25 to-transparent" />
        </div>
      </div>
    </div>
  );
}

/**
 * Éclaircit ou assombrit une couleur hex.
 *
 * @param hex Couleur hex
 * @param percent Pourcentage de variation
 * @returns Couleur RGB
 */
function shade(hex: string, percent: number): string {
  const normalized = hex.replace("#", "");
  const num = parseInt(normalized, 16);
  let r = (num >> 16) & 0xff;
  let g = (num >> 8) & 0xff;
  let b = num & 0xff;
  const target = percent < 0 ? 0 : 255;
  const factor = Math.abs(percent) / 100;
  r = Math.round((target - r) * factor) + r;
  g = Math.round((target - g) * factor) + g;
  b = Math.round((target - b) * factor) + b;
  return `rgb(${r}, ${g}, ${b})`;
}
