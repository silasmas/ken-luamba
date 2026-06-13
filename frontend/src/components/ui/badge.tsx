import * as React from "react";
import { cva, type VariantProps } from "class-variance-authority";
import { cn } from "@/lib/utils";

const badgeVariants = cva(
  "inline-flex items-center gap-1.5 rounded-full font-medium tracking-wide transition-colors",
  {
    variants: {
      variant: {
        default: "bg-ink/[0.04] text-ink/70 border border-ink/10",
        accent: "bg-accent-soft text-accent border border-accent/15",
        solid: "bg-ink text-paper",
        outline: "border border-ink/20 text-ink/70",
      },
      size: {
        sm: "px-2.5 py-0.5 text-[0.65rem] uppercase tracking-[0.18em]",
        md: "px-3 py-1 text-xs",
      },
    },
    defaultVariants: { variant: "default", size: "sm" },
  },
);

export interface BadgeProps
  extends React.HTMLAttributes<HTMLSpanElement>,
    VariantProps<typeof badgeVariants> {}

/**
 * Badge du design system éditorial.
 */
export function Badge({ className, variant, size, ...props }: BadgeProps) {
  return (
    <span className={cn(badgeVariants({ variant, size, className }))} {...props} />
  );
}
