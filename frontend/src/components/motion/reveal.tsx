"use client";

import { motion, type Variants } from "framer-motion";
import type { ReactNode } from "react";

const easePremium = [0.16, 1, 0.3, 1] as const;

const variants: Variants = {
  hidden: { opacity: 0, y: 26 },
  visible: (i: number = 0) => ({
    opacity: 1,
    y: 0,
    transition: {
      duration: 0.85,
      ease: easePremium,
      delay: i * 0.08,
    },
  }),
};

/**
 * Animation d'apparition au scroll.
 * Ne pas envelopper les boutons, formulaires ou panneaux d'achat :
 * utiliser `eager` ou aucun wrapper Reveal.
 */
export function Reveal({
  children,
  className,
  delay = 0,
  as = "div",
  once = true,
  eager = false,
}: {
  children: ReactNode;
  className?: string;
  delay?: number;
  as?: "div" | "section" | "li" | "span";
  once?: boolean;
  /** Affiche immédiatement sans attendre le scroll (composants interactifs). */
  eager?: boolean;
}) {
  const MotionTag = motion[as];

  if (eager) {
    return <MotionTag className={className}>{children}</MotionTag>;
  }

  return (
    <MotionTag
      className={className}
      variants={variants}
      custom={delay}
      initial="hidden"
      whileInView="visible"
      viewport={{ once, margin: "-80px" }}
    >
      {children}
    </MotionTag>
  );
}

/**
 * Conteneur avec apparition en cascade.
 */
export function Stagger({
  children,
  className,
  delayChildren = 0,
  stagger = 0.1,
}: {
  children: ReactNode;
  className?: string;
  delayChildren?: number;
  stagger?: number;
}) {
  return (
    <motion.div
      className={className}
      initial="hidden"
      whileInView="visible"
      viewport={{ once: true, margin: "-60px" }}
      variants={{
        hidden: {},
        visible: {
          transition: { staggerChildren: stagger, delayChildren },
        },
      }}
    >
      {children}
    </motion.div>
  );
}

/**
 * Élément enfant d'une animation en cascade.
 */
export function StaggerItem({
  children,
  className,
}: {
  children: ReactNode;
  className?: string;
}) {
  return (
    <motion.div className={className} variants={variants}>
      {children}
    </motion.div>
  );
}
