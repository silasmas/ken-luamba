"use client";

import { useEffect, useRef, useState } from "react";

interface QrScannerModalProps {
  /** Indique si le modal est ouvert */
  isOpen: boolean;
  /** Callback de fermeture */
  onClose: () => void;
  /**
   * Callback appelé avec le texte scanné (token QR).
   *
   * @param decodedText Contenu du QR code
   */
  onScan: (decodedText: string) => void;
}

/**
 * Modal de scan QR code via la caméra (html5-qrcode).
 */
export function QrScannerModal({ isOpen, onClose, onScan }: QrScannerModalProps) {
  const readerRef = useRef<HTMLDivElement>(null);
  const scannerRef = useRef<{ stop: () => Promise<void> } | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!isOpen) {
      return;
    }

    let cancelled = false;

    const startScanner = async () => {
      setError(null);

      try {
        const { Html5Qrcode } = await import("html5-qrcode");
        const elementId = "qr-reader";

        if (readerRef.current) {
          readerRef.current.id = elementId;
        }

        const scanner = new Html5Qrcode(elementId);
        scannerRef.current = scanner;

        const cameras = await Html5Qrcode.getCameras();

        if (cameras.length === 0) {
          setError("Aucune caméra détectée sur cet appareil.");
          return;
        }

        const rearCamera = cameras.find((camera) =>
          /back|rear|environment/i.test(camera.label),
        );
        const cameraId = rearCamera?.id ?? cameras[cameras.length - 1].id;

        await scanner.start(
          cameraId,
          { fps: 10, qrbox: { width: 250, height: 250 } },
          (decodedText) => {
            if (cancelled) {
              return;
            }

            void scanner.stop().then(() => {
              scannerRef.current = null;
              onScan(decodedText.trim());
              onClose();
            });
          },
          () => {},
        );
      } catch (err) {
        if (!cancelled) {
          setError(
            err instanceof Error
              ? err.message
              : "Impossible d'accéder à la caméra. Autorisez l'accès ou saisissez le code manuellement.",
          );
        }
      }
    };

    void startScanner();

    return () => {
      cancelled = true;

      if (scannerRef.current) {
        void scannerRef.current.stop().catch(() => {});
        scannerRef.current = null;
      }
    };
  }, [isOpen, onClose, onScan]);

  if (!isOpen) {
    return null;
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
      <div className="w-full max-w-md rounded-xl bg-white p-5 shadow-xl">
        <div className="mb-4 flex items-center justify-between">
          <h3 className="font-semibold text-stone-900">Scanner le QR client</h3>
          <button
            type="button"
            onClick={onClose}
            className="rounded-lg px-3 py-1 text-sm text-stone-600 hover:bg-stone-100"
          >
            Fermer
          </button>
        </div>

        <p className="mb-3 text-sm text-stone-600">
          Placez le QR code du client dans le cadre. La vérification démarrera automatiquement.
        </p>

        {error && (
          <p className="mb-3 rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>
        )}

        <div
          ref={readerRef}
          className="overflow-hidden rounded-lg border border-stone-200"
        />
      </div>
    </div>
  );
}
