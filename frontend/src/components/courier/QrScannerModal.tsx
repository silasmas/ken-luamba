"use client";

import { useEffect, useId, useRef, useState } from "react";

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

const QR_READER_MIN_HEIGHT_PX = 300;

/**
 * Attend que le conteneur du scanner soit monté dans le DOM.
 *
 * @param elementId Identifiant HTML du conteneur
 * @returns Promise résolue quand l'élément est disponible
 */
function waitForReaderElement(elementId: string): Promise<HTMLElement> {
  return new Promise((resolve, reject) => {
    let attempts = 0;

    const check = () => {
      const element = document.getElementById(elementId);

      if (element) {
        resolve(element);
        return;
      }

      attempts += 1;

      if (attempts >= 20) {
        reject(new Error("Conteneur du scanner introuvable."));
        return;
      }

      requestAnimationFrame(check);
    };

    requestAnimationFrame(check);
  });
}

/**
 * Modal de scan QR code via la caméra (html5-qrcode).
 */
export function QrScannerModal({ isOpen, onClose, onScan }: QrScannerModalProps) {
  const reactId = useId().replace(/:/g, "");
  const readerElementId = `qr-reader-${reactId}`;
  const scannerRef = useRef<{ stop: () => Promise<void>; clear: () => void } | null>(null);
  const onScanRef = useRef(onScan);
  const onCloseRef = useRef(onClose);
  const [error, setError] = useState<string | null>(null);
  const [isStarting, setIsStarting] = useState(false);

  onScanRef.current = onScan;
  onCloseRef.current = onClose;

  useEffect(() => {
    if (!isOpen) {
      return;
    }

    let cancelled = false;

    /**
     * Démarre la caméra et le décodage QR.
     */
    const startScanner = async () => {
      setError(null);
      setIsStarting(true);

      try {
        await waitForReaderElement(readerElementId);

        if (cancelled) {
          return;
        }

        const { Html5Qrcode } = await import("html5-qrcode");
        const scanner = new Html5Qrcode(readerElementId, false);
        scannerRef.current = scanner;

        const scanConfig = {
          fps: 10,
          qrbox: (viewfinderWidth: number, viewfinderHeight: number) => {
            const edge = Math.min(viewfinderWidth, viewfinderHeight);
            const size = Math.max(Math.floor(edge * 0.65), 180);

            return { width: size, height: size };
          },
          aspectRatio: 1.333,
          disableFlip: false,
        };

        /**
         * Callback succès scan : arrête la caméra puis remonte le token.
         *
         * @param decodedText Contenu décodé
         */
        const handleDecoded = (decodedText: string) => {
          if (cancelled) {
            return;
          }

          cancelled = true;

          void scanner
            .stop()
            .then(() => {
              scanner.clear();
              scannerRef.current = null;
              onScanRef.current(decodedText.trim());
              onCloseRef.current();
            })
            .catch(() => {
              onScanRef.current(decodedText.trim());
              onCloseRef.current();
            });
        };

        const cameras = await Html5Qrcode.getCameras();

        if (cameras.length === 0) {
          setError("Aucune caméra détectée sur cet appareil.");
          return;
        }

        const rearCamera = cameras.find((camera) =>
          /back|rear|arrière|environment/i.test(camera.label),
        );
        const preferredCameraId = rearCamera?.id ?? cameras[0].id;
        const cameraAttempts: Array<string | MediaTrackConstraints> = [
          preferredCameraId,
          { facingMode: { ideal: "environment" } },
          { facingMode: { ideal: "user" } },
          cameras[0].id,
        ];

        let lastError: unknown = null;

        for (const cameraConfig of cameraAttempts) {
          if (cancelled) {
            return;
          }

          try {
            await scanner.start(cameraConfig, scanConfig, handleDecoded, () => {});
            return;
          } catch (attemptError) {
            lastError = attemptError;

            try {
              await scanner.stop();
            } catch {
              // Ignore si la caméra n'avait pas encore démarré
            }
          }
        }

        throw lastError instanceof Error
          ? lastError
          : new Error("Impossible d'ouvrir la caméra.");
      } catch (err) {
        if (!cancelled) {
          setError(
            err instanceof Error
              ? err.message
              : "Impossible d'accéder à la caméra. Autorisez l'accès ou saisissez le code manuellement.",
          );
        }
      } finally {
        if (!cancelled) {
          setIsStarting(false);
        }
      }
    };

    void startScanner();

    return () => {
      cancelled = true;

      if (scannerRef.current) {
        const scanner = scannerRef.current;
        scannerRef.current = null;

        void scanner
          .stop()
          .then(() => {
            scanner.clear();
          })
          .catch(() => {});
      }
    };
  }, [isOpen, readerElementId]);

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

        {isStarting && !error && (
          <p className="mb-3 text-sm text-stone-500">Activation de la caméra...</p>
        )}

        <div
          id={readerElementId}
          className="qr-scanner-reader overflow-hidden rounded-lg border border-stone-200 bg-black"
          style={{ minHeight: QR_READER_MIN_HEIGHT_PX }}
        />
      </div>
    </div>
  );
}
