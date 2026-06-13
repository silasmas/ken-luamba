import Image from "next/image";
import Link from "next/link";
import { cn } from "@/lib/utils";

/**
 * Logo Ken Luamba avec assets importés du book-site.
 */
export function BrandMark({
  className,
  withWordmark = true,
  tone = "ink",
  size = "md",
}: {
  className?: string;
  withWordmark?: boolean;
  tone?: "ink" | "paper";
  size?: "md" | "lg";
}) {
  const src = tone === "paper" ? "/images/logo-kl.png" : "/images/logo-kl-black.png";
  const mark = size === "lg" ? "h-16 w-16" : "h-13 w-13";

  return (
    <Link
      href="/"
      className={cn("group inline-flex items-center gap-3", className)}
      aria-label="Ken Luamba — accueil"
    >
      <span className="relative inline-flex items-center justify-center overflow-hidden">
        <Image
          src={src}
          alt="Logo Ken Luamba"
          width={96}
          height={96}
          className={cn(
            mark,
            "object-contain transition-transform duration-700 group-hover:rotate-[6deg]",
          )}
          priority
        />
      </span>
      {withWordmark && (
        <span className="flex flex-col leading-none">
          <span
            className={cn(
              "font-display tracking-tight",
              size === "lg" ? "text-[1.2rem]" : "text-[1.05rem]",
              tone === "paper" ? "text-paper" : "text-ink",
            )}
          >
            Ken Luamba
          </span>
          <span
            className={cn(
              "mt-1 text-[0.55rem] uppercase tracking-[0.3em]",
              tone === "paper" ? "text-paper/60" : "text-ink/45",
            )}
          >
            Éditions
          </span>
        </span>
      )}
    </Link>
  );
}
