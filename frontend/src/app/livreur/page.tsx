"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { PhotoProofInput } from "@/components/courier/PhotoProofInput";
import { QrScannerModal } from "@/components/courier/QrScannerModal";
import {
  acceptCourierDelivery,
  confirmCourierDelivery,
  fetchCourierDeliveries,
  scanCourierQr,
  type CourierDelivery,
  type CourierScanResult,
} from "@/lib/api/courier";
import { useAuthStore } from "@/stores/authStore";

/**
 * Interface livreur — courses, scan QR et confirmation avec photo.
 */
export default function LivreurPage() {
  const token = useAuthStore((state) => state.token);
  const user = useAuthStore((state) => state.user);
  const isReady = useAuthStore((state) => state.isReady);

  const [mine, setMine] = useState<CourierDelivery[]>([]);
  const [available, setAvailable] = useState<CourierDelivery[]>([]);
  const [activeTab, setActiveTab] = useState<"mine" | "available" | "scan">("mine");
  const [tokenQr, setTokenQr] = useState("");
  const [scanResult, setScanResult] = useState<CourierScanResult | null>(null);
  const [photo, setPhoto] = useState<File | null>(null);
  const [comment, setComment] = useState("");
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [scannerOpen, setScannerOpen] = useState(false);

  /**
   * Recharge les courses assignées et disponibles.
   */
  const loadDeliveries = async () => {
    if (!token) {
      return;
    }

    const data = await fetchCourierDeliveries(token);
    setMine(data.mine);
    setAvailable(data.available);
  };

  useEffect(() => {
    if (!token) {
      return;
    }

    void loadDeliveries().catch((err) => {
      setError(err instanceof Error ? err.message : "Erreur de chargement");
    });
  }, [token]);

  /**
   * Prend en charge une course disponible.
   *
   * @param deliveryId Identifiant livraison
   */
  const handleAccept = async (deliveryId: string) => {
    if (!token) {
      return;
    }

    setError(null);
    setMessage(null);

    try {
      const response = await acceptCourierDelivery(token, deliveryId);
      setMessage(response.message);
      await loadDeliveries();
      setActiveTab("mine");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Erreur");
    }
  };

  /**
   * Vérifie le QR code saisi ou scanné.
   *
   * @param scannedToken Token optionnel issu du scanner caméra
   */
  const handleScan = async (scannedToken?: string) => {
    const value = (scannedToken ?? tokenQr).trim();

    if (!token || value === "") {
      return;
    }

    setTokenQr(value);
    setIsLoading(true);
    setError(null);

    try {
      const result = await scanCourierQr(token, value);
      setScanResult(result);
      setActiveTab("scan");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Scan échoué");
      setScanResult(null);
    } finally {
      setIsLoading(false);
    }
  };

  /**
   * Traite un QR scanné via la caméra.
   *
   * @param decodedText Contenu du QR code
   */
  const handleQrScanned = (decodedText: string) => {
    void handleScan(decodedText);
  };

  /**
   * Confirme la livraison avec photo preuve.
   */
  const handleConfirm = async () => {
    if (!token || tokenQr.trim() === "") {
      return;
    }

    if (scanResult?.fulfillmentType === "delivery" && !photo) {
      setError("Une photo de preuve est requise pour une livraison à domicile.");
      return;
    }

    setIsLoading(true);
    setError(null);

    try {
      const response = await confirmCourierDelivery(
        token,
        tokenQr.trim(),
        photo,
        comment || undefined,
      );
      setMessage(response.message);
      setScanResult(null);
      setTokenQr("");
      setPhoto(null);
      setComment("");
      await loadDeliveries();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Confirmation échouée");
    } finally {
      setIsLoading(false);
    }
  };

  if (!isReady) {
    return <p className="py-20 text-center text-stone-600">Chargement...</p>;
  }

  if (!token || user?.role !== "courier") {
    return (
      <div className="py-20 text-center">
        <p className="text-stone-600">Connexion livreur requise.</p>
        <Link href="/connexion?redirect=/livreur" className="mt-4 inline-block text-amber-700 hover:underline">
          Se connecter (compte livreur)
        </Link>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-stone-900">Espace livreur</h1>
        <p className="mt-1 text-sm text-stone-600">
          Bonjour {user.fullName}. Prenez une course, scannez le QR client, confirmez avec photo.
        </p>
      </div>

      <div className="flex flex-wrap gap-2">
        {[
          { id: "mine" as const, label: `Mes courses (${mine.length})` },
          { id: "available" as const, label: `Disponibles (${available.length})` },
          { id: "scan" as const, label: "Scanner QR" },
        ].map((tab) => (
          <button
            key={tab.id}
            type="button"
            onClick={() => setActiveTab(tab.id)}
            className={`rounded-lg px-4 py-2 text-sm font-medium ${
              activeTab === tab.id ? "bg-stone-900 text-white" : "bg-stone-100 text-stone-700"
            }`}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {message && <p className="rounded-lg bg-green-50 p-3 text-sm text-green-800">{message}</p>}
      {error && <p className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}

      {activeTab === "mine" && (
        <div className="space-y-4">
          {mine.length === 0 ? (
            <p className="text-stone-600">Aucune course en cours. Consultez l&apos;onglet Disponibles.</p>
          ) : (
            mine.map((delivery) => (
              <DeliveryCard
                key={delivery.id}
                delivery={delivery}
                actionLabel="Ouvrir le scan"
                onAction={() => {
                  setActiveTab("scan");
                }}
              />
            ))
          )}
        </div>
      )}

      {activeTab === "available" && (
        <div className="space-y-4">
          {available.length === 0 ? (
            <p className="text-stone-600">Aucune course disponible pour le moment.</p>
          ) : (
            available.map((delivery) => (
              <DeliveryCard
                key={delivery.id}
                delivery={delivery}
                actionLabel="Prendre en charge"
                onAction={() => void handleAccept(delivery.id)}
              />
            ))
          )}
        </div>
      )}

      {activeTab === "scan" && (
        <section className="space-y-4 rounded-xl border border-stone-200 bg-white p-5">
          <h2 className="font-semibold text-stone-900">Scanner / confirmer</h2>
          <p className="text-sm text-stone-600">
            Scannez le QR client avec la caméra ou saisissez le token manuellement.
          </p>
          <input
            value={tokenQr}
            onChange={(event) => setTokenQr(event.target.value)}
            placeholder="Token QR du client"
            className="w-full rounded-lg border border-stone-300 px-4 py-2 font-mono text-xs"
          />
          <div className="flex flex-wrap gap-3">
            <button
              type="button"
              disabled={isLoading}
              onClick={() => setScannerOpen(true)}
              className="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
            >
              Ouvrir le scanner
            </button>
            <button
              type="button"
              disabled={isLoading}
              onClick={() => void handleScan()}
              className="rounded-lg bg-stone-800 px-4 py-2 text-sm text-white disabled:opacity-60"
            >
              Vérifier le QR
            </button>
          </div>

          {scanResult && (
            <div className="rounded-lg bg-stone-50 p-4 text-sm">
              <p className="font-semibold">{scanResult.orderNumber}</p>
              <p className="text-stone-600">{scanResult.statusLabel} — {scanResult.fulfillmentLabel}</p>
              <p className="mt-2">Client : {scanResult.customerName ?? "—"}</p>
              {scanResult.shippingAddress && (
                <p className="mt-1">
                  Adresse : {scanResult.shippingAddress.street}, {scanResult.shippingAddress.commune},{" "}
                  {scanResult.shippingAddress.city}
                </p>
              )}
              {scanResult.pickupPoint && (
                <p className="mt-1">
                  Retrait : {scanResult.pickupPoint.name} — {scanResult.pickupPoint.address}
                </p>
              )}
              <ul className="mt-3 list-disc pl-5">
                {scanResult.items.map((item, index) => (
                  <li key={`${item.bookTitle}-${index}`}>
                    {item.bookTitle} ({item.formatLabel}) × {item.quantity}
                  </li>
                ))}
              </ul>
            </div>
          )}

          {scanResult && !scanResult.qrUsed && (
            <div className="space-y-3 border-t border-stone-200 pt-4">
              <PhotoProofInput
                photo={photo}
                onPhotoChange={setPhoto}
                required={scanResult.fulfillmentType === "delivery"}
              />
              <textarea
                value={comment}
                onChange={(event) => setComment(event.target.value)}
                placeholder="Commentaire (optionnel)"
                rows={2}
                className="w-full rounded-lg border border-stone-300 px-4 py-2 text-sm"
              />
              <button
                type="button"
                disabled={isLoading}
                onClick={() => void handleConfirm()}
                className="rounded-lg bg-green-600 px-5 py-2 text-sm font-semibold text-white disabled:opacity-60"
              >
                Confirmer la livraison
              </button>
            </div>
          )}

          {scanResult?.qrUsed && (
            <p className="text-sm text-amber-700">Ce QR code a déjà été utilisé.</p>
          )}
        </section>
      )}

      <QrScannerModal
        isOpen={scannerOpen}
        onClose={() => setScannerOpen(false)}
        onScan={handleQrScanned}
      />
    </div>
  );
}

/**
 * Carte résumé d'une course livreur.
 */
function DeliveryCard({
  delivery,
  actionLabel,
  onAction,
}: {
  delivery: CourierDelivery;
  actionLabel: string;
  onAction: () => void;
}) {
  return (
    <article className="rounded-xl border border-stone-200 bg-white p-5">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <p className="font-semibold text-stone-900">{delivery.orderNumber}</p>
          <p className="text-sm text-stone-500">{delivery.statusLabel} — {delivery.fulfillmentLabel}</p>
          <p className="mt-1 text-sm">Client : {delivery.customerName ?? "—"}</p>
        </div>
        <button
          type="button"
          onClick={onAction}
          className="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white"
        >
          {actionLabel}
        </button>
      </div>
      {delivery.shippingAddress && (
        <p className="mt-3 text-sm text-stone-600">
          {delivery.shippingAddress.street}, {delivery.shippingAddress.commune}, {delivery.shippingAddress.city}
        </p>
      )}
      {delivery.pickupPoint && (
        <p className="mt-3 text-sm text-stone-600">
          Point de retrait : {delivery.pickupPoint.name} — {delivery.pickupPoint.address}
        </p>
      )}
    </article>
  );
}
