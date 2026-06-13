"use client";

interface ConfirmDialogProps {
  /** Indique si le dialogue est ouvert */
  isOpen: boolean;
  /** Titre du dialogue */
  title: string;
  /** Message de confirmation */
  message: string;
  /** Libellé du bouton de confirmation */
  confirmLabel: string;
  /** Variante visuelle (danger pour contestation) */
  variant?: "default" | "danger";
  /** Indique si l'action est en cours */
  isLoading?: boolean;
  /** Callback de fermeture sans action */
  onCancel: () => void;
  /** Callback de confirmation */
  onConfirm: () => void;
}

/**
 * Dialogue de confirmation en deux temps avant une action irréversible.
 */
export function ConfirmDialog({
  isOpen,
  title,
  message,
  confirmLabel,
  variant = "default",
  isLoading = false,
  onCancel,
  onConfirm,
}: ConfirmDialogProps) {
  if (!isOpen) {
    return null;
  }

  const confirmClass =
    variant === "danger"
      ? "bg-red-600 text-white hover:bg-red-700"
      : "bg-green-600 text-white hover:bg-green-700";

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div
        role="dialog"
        aria-modal="true"
        className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl"
      >
        <h3 className="text-lg font-semibold text-stone-900">{title}</h3>
        <p className="mt-3 text-sm text-stone-600">{message}</p>
        <div className="mt-6 flex flex-wrap justify-end gap-3">
          <button
            type="button"
            disabled={isLoading}
            onClick={onCancel}
            className="rounded-lg border border-stone-300 px-4 py-2 text-sm text-stone-700 disabled:opacity-60"
          >
            Annuler
          </button>
          <button
            type="button"
            disabled={isLoading}
            onClick={onConfirm}
            className={`rounded-lg px-4 py-2 text-sm font-semibold disabled:opacity-60 ${confirmClass}`}
          >
            {isLoading ? "Traitement..." : confirmLabel}
          </button>
        </div>
      </div>
    </div>
  );
}
