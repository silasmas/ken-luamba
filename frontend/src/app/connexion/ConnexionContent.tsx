"use client";

import { useRouter, useSearchParams } from "next/navigation";
import { useState } from "react";
import { requestLoginOtp, requestRegisterOtp, verifyOtp } from "@/lib/api/auth";
import { useAuthStore } from "@/stores/authStore";
import { useCartStore } from "@/stores/cartStore";

/**
 * Contenu de la page connexion / inscription OTP.
 */
export default function ConnexionContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const setSession = useAuthStore((state) => state.setSession);
  const redirectTo = searchParams.get("redirect") ?? "/";

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
    <div className="mx-auto max-w-md">
      <h1 className="text-2xl font-bold text-stone-900">
        {mode === "login" ? "Connexion" : "Inscription"}
      </h1>
      <p className="mt-2 text-sm text-stone-600">
        Authentification sécurisée par code OTP envoyé par email.
      </p>

      <div className="mt-6 flex gap-2">
        <button
          type="button"
          onClick={() => {
            setMode("login");
            setStep("form");
          }}
          className={`rounded-lg px-4 py-2 text-sm font-medium ${
            mode === "login" ? "bg-amber-600 text-white" : "bg-stone-200 text-stone-700"
          }`}
        >
          Connexion
        </button>
        <button
          type="button"
          onClick={() => {
            setMode("register");
            setStep("form");
          }}
          className={`rounded-lg px-4 py-2 text-sm font-medium ${
            mode === "register" ? "bg-amber-600 text-white" : "bg-stone-200 text-stone-700"
          }`}
        >
          Inscription
        </button>
      </div>

      {error && <p className="mt-4 text-sm text-red-600">{error}</p>}
      {info && <p className="mt-4 text-sm text-green-700">{info}</p>}

      {step === "form" ? (
        <form onSubmit={handleSendOtp} className="mt-6 space-y-4">
          {mode === "register" && (
            <div>
              <label className="block text-sm font-medium text-stone-700">Nom complet</label>
              <input
                type="text"
                required
                value={fullName}
                onChange={(event) => setFullName(event.target.value)}
                className="mt-1 w-full rounded-lg border border-stone-300 px-4 py-2"
              />
            </div>
          )}
          <div>
            <label className="block text-sm font-medium text-stone-700">Email</label>
            <input
              type="email"
              required
              value={email}
              onChange={(event) => setEmail(event.target.value)}
              className="mt-1 w-full rounded-lg border border-stone-300 px-4 py-2"
            />
          </div>
          <button
            type="submit"
            disabled={isLoading}
            className="w-full rounded-lg bg-amber-600 px-6 py-3 text-sm font-semibold text-white disabled:opacity-60"
          >
            {isLoading ? "Envoi..." : "Recevoir le code OTP"}
          </button>
        </form>
      ) : (
        <form onSubmit={handleVerifyOtp} className="mt-6 space-y-4">
          <div>
            <label className="block text-sm font-medium text-stone-700">Code OTP</label>
            <input
              type="text"
              required
              maxLength={6}
              value={code}
              onChange={(event) => setCode(event.target.value)}
              className="mt-1 w-full rounded-lg border border-stone-300 px-4 py-2 tracking-widest"
            />
          </div>
          <button
            type="submit"
            disabled={isLoading}
            className="w-full rounded-lg bg-amber-600 px-6 py-3 text-sm font-semibold text-white disabled:opacity-60"
          >
            {isLoading ? "Vérification..." : "Valider et continuer"}
          </button>
          <button
            type="button"
            onClick={() => setStep("form")}
            className="w-full text-sm text-stone-600 hover:underline"
          >
            Modifier l&apos;email
          </button>
        </form>
      )}
    </div>
  );
}
