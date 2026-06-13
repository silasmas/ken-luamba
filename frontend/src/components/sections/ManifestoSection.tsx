import { Reveal } from "@/components/motion/reveal";

/**
 * Bandeau manifeste éditorial (contenu statique).
 */
export function ManifestoSection() {
  return (
    <section className="relative border-y border-ink/[0.06] bg-paper py-20 lg:py-28">
      <div className="mx-auto max-w-4xl px-6 text-center lg:px-10">
        <Reveal>
          <span className="eyebrow">Notre conviction</span>
        </Reveal>
        <Reveal delay={1}>
          <p className="mt-6 font-quote text-[1.7rem] leading-[1.4] text-ink/85 text-balance sm:text-[2.1rem] lg:text-[2.5rem]">
            Un livre n&apos;est pas un produit. C&apos;est une rencontre. Ici, chaque
            ouvrage est pensé comme une étape sur un chemin —{" "}
            <span className="text-ink">celui d&apos;une foi enracinée, lucide et debout.</span>
          </p>
        </Reveal>
      </div>
    </section>
  );
}
