"use client";

import { useEffect, useRef } from "react";
import QRCode from "qrcode";

interface OrderQrCodeProps {
  token: string;
  orderNumber: string;
  title?: string;
}

/**
 * Affiche un QR code commande et permet son téléchargement en PNG.
 *
 * @param token Jeton unique scanné par le livreur
 * @param orderNumber Numéro de commande pour le nom de fichier
 * @param title Titre affiché au-dessus du QR
 */
export function OrderQrCode({ token, orderNumber, title = "QR code retrait / livraison" }: OrderQrCodeProps) {
  const canvasRef = useRef<HTMLCanvasElement>(null);

  useEffect(() => {
    const canvas = canvasRef.current;

    if (!canvas || !token) {
      return;
    }

    void QRCode.toCanvas(canvas, token, {
      width: 256,
      margin: 2,
      errorCorrectionLevel: "M",
    });
  }, [token]);

  /**
   * Télécharge le QR code affiché au format PNG.
   */
  const handleDownload = () => {
    const canvas = canvasRef.current;

    if (!canvas) {
      return;
    }

    const link = document.createElement("a");
    link.download = `qr-${orderNumber}.png`;
    link.href = canvas.toDataURL("image/png");
    link.click();
  };

  return (
    <div className="rounded-xl border border-green-200 bg-green-50 p-5 text-center">
      <p className="font-semibold text-green-900">{title}</p>
      <p className="mt-1 text-xs text-green-700">
        Présentez ce code au livreur ou au point de retrait.
      </p>
      <div className="mt-4 flex justify-center">
        <canvas ref={canvasRef} className="rounded-lg bg-white p-2 shadow-sm" />
      </div>
      <p className="mt-3 text-xs text-stone-500">Commande {orderNumber}</p>
      <button
        type="button"
        onClick={handleDownload}
        className="mt-4 rounded-lg bg-green-700 px-5 py-2 text-sm font-semibold text-white hover:bg-green-800"
      >
        Télécharger le QR code
      </button>
    </div>
  );
}
