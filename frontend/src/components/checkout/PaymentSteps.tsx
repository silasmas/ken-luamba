import type { PaymentStep } from "@/lib/mobileMoney";

interface PaymentStepsProps {
  steps: PaymentStep[];
  message?: string | null;
}

/**
 * Affiche les étapes de paiement Mobile Money sous le bouton payer.
 */
export function PaymentSteps({ steps, message }: PaymentStepsProps) {
  if (steps.length === 0) {
    return null;
  }

  return (
    <div className="rounded-xl border border-stone-200 bg-stone-50 p-4">
      <h3 className="text-sm font-semibold text-stone-900">Suivi du paiement</h3>
      <ol className="mt-3 space-y-2">
        {steps.map((step) => (
          <li key={step.id} className="flex items-start gap-3 text-sm">
            <span
              className={`mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-xs font-bold ${
                step.status === "done"
                  ? "bg-green-600 text-white"
                  : step.status === "active"
                    ? "bg-amber-600 text-white"
                    : step.status === "error"
                      ? "bg-red-600 text-white"
                      : "bg-stone-300 text-stone-600"
              }`}
            >
              {step.status === "done" ? "✓" : step.status === "error" ? "!" : "•"}
            </span>
            <span
              className={
                step.status === "error"
                  ? "text-red-700"
                  : step.status === "active"
                    ? "font-medium text-amber-800"
                    : "text-stone-700"
              }
            >
              {step.label}
            </span>
          </li>
        ))}
      </ol>
      {message && (
        <p className="mt-3 text-sm text-stone-600">{message}</p>
      )}
    </div>
  );
}
