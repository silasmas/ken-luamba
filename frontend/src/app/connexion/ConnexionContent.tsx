"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useState } from "react";
import { Mail, ArrowRight } from "lucide-react";
import { BrandMark } from "@/components/brand/BrandMark";
import { Button } from "@/components/ui/button";
import { requestLoginOtp, requestRegisterOtp, verifyOtp } from "@/lib/api/auth";
import { useAuthStore } from "@/stores/authStore";
import { useCartStore } from "@/stores/cartStore";

/**
 * Contenu de la page connexion / inscription OTP — habillage éditorial.
 */
export default function ConnexionContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const setSession = useAuthStore((state) => state.setSession);
  const redirectTo = searchParams.get("redirect") ?? "/espace/commandes";

  const [mode, setMode] = useState<"login" | "register">("login");
  const [step, setStep] = useState<"form" | "otp">("form");
  const [email, setEmail] = useState("");
  const [fullName, setFullName] = useState("");
  const [code, setCode] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [info, setInfo] = useState<string | null>(null);

  /**
   * Envoie le code OTP par email.
   */
  const handleSendOtp = async (event: React.FormEvent) => {
    event.preventDefault();
    setIsLoading(true);
    setError(null);
    setInfo(null);

    try {
      if (mode === "register") {
        await requestRegisterOtp(email, fullName);
      } else {
        await requestLoginOtp(email);
      }

      setInfo("Code OTP envoyé par email. Consultez votre boîte de réception.");
      setStep("otp");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Erreur d'envoi OTP");
    } finally {
      setIsLoading(false);
    }
  };

  /**
   * Vérifie le code OTP et redirige.
   */
  const handleVerifyOtp = async (event: React.FormEvent) => {
    event.preventDefault();
    setIsLoading(true);
    setError(null);

    try {
      const response = await verifyOtp(email, code, mode);
      setSession(response.token, response.user);
      await useCartStore.getState().initCart();
      router.push(redirectTo);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Code OTP invalide");
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="relative grid min-h-[calc(100vh-4.5rem)] lg:grid-cols-2">
      <div className="flex items-center justify-center px-6 py-16 lg:px-16">
        <div className="w-full max-w-sm">
          <BrandMark />

          <h1 className="mt-12 font-display text-4xl leading-tight tracking-tight text-ink lg:text-5xl">
            Votre espace de lecture.
          </h1>
          <p className="mt-4 text-[1.05rem] leading-relaxed text-ink/60">
            Connexion sécurisée par code OTP envoyé par email. Aucun mot de passe requis.
          </p>

          <div className="mt-6 flex gap-2">
            <Button
              type="button"
              variant={mode === "login" ? "primary" : "outline"}
              size="sm"
              onClick={() => {
                setMode("login");
                setStep("form");
              }}
            >
              Connexion
            </Button>
            <Button
              type="button"
              variant={mode === "register" ? "primary" : "outline"}
              size="sm"
              onClick={() => {
                setMode("register");
                setStep("form");
              }}
            >
              Inscription
            </Button>
          </div>

          {error && <p className="mt-4 text-sm text-red-600">{error}</p>}
          {info && <p className="mt-4 text-sm text-green-700">{info}</p>}

          {step === "form" ? (
            <form onSubmit={handleSendOtp} className="mt-8 space-y-4">
              {mode === "register" && (
                <input
                  type="text"
                  required
                  placeholder="Nom complet"
                  value={fullName}
                  onChange={(event) => setFullName(event.target.value)}
                  className="h-13 w-full rounded-full border border-ink/15 bg-white/60 px-5 text-sm text-ink outline-none focus:border-ink/40"
                />
              )}
              <div className="relative">
                <Mail className="absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-ink/35" />
                <input
                  type="email"
                  required
                  placeholder="vous@exemple.com"
                  value={email}
                  onChange={(event) => setEmail(event.target.value)}
                  className="h-13 w-full rounded-full border border-ink/15 bg-white/60 py-3.5 pl-11 pr-5 text-sm text-ink outline-none focus:border-ink/40"
                />
              </div>
              <Button type="submit" variant="primary" size="lg" className="w-full" disabled={isLoading}>
                {isLoading ? "Envoi..." : "Recevoir le code OTP"}
                <ArrowRight className="h-4 w-4" />
              </Button>
            </form>
          ) : (
            <form onSubmit={handleVerifyOtp} className="mt-8 space-y-4">
              <input
                type="text"
                required
                maxLength={6}
                placeholder="Code OTP"
                value={code}
                onChange={(event) => setCode(event.target.value)}
                className="h-13 w-full rounded-full border border-ink/15 bg-white/60 px-5 text-center text-sm tracking-[0.4em] outline-none"
              />
              <Button type="submit" variant="primary" size="lg" className="w-full" disabled={isLoading}>
                {isLoading ? "Vérification..." : "Valider et continuer"}
              </Button>
              <button
                type="button"
                onClick={() => setStep("form")}
                className="w-full text-sm text-ink/55 hover:text-ink"
              >
                Modifier l&apos;email
              </button>
            </form>
          )}

          <p className="mt-8 text-xs text-ink/45">
            Pas encore de livre ?{" "}
            <Link href="/livres" className="text-accent hover:underline">
              Explorer la bibliothèque
            </Link>
          </p>
        </div>
      </div>

      <div className="relative hidden overflow-hidden bg-midnight lg:block">
        <div className="relative flex h-full flex-col justify-between p-16 text-white">
          <span className="eyebrow !text-white/45">Espace membre</span>
          <blockquote className="font-quote text-4xl leading-[1.25] tracking-tight text-balance">
            « La foi qui tient n&apos;est pas celle qui crie le plus fort. C&apos;est
            celle qui demeure quand tout vacille. »
          </blockquote>
          <p className="text-sm text-white/55">Extrait — L&apos;Église face aux défis de l&apos;heure</p>
        </div>
      </div>
    </div>
  );
}
