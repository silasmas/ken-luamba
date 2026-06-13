"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useEffect, useMemo, useRef, useState } from "react";
import {
  checkPaymentStatus,
  createOrder,
  fetchOrder,
  fetchPickupPoints,
  initiatePayment,
} from "@/lib/api/orders";
import {
  fetchShippingConfig,
  fetchShippingQuote,
  type ShippingConfig,
  type ShippingQuote,
} from "@/lib/api/shipping";
import { PaymentSteps } from "@/components/checkout/PaymentSteps";
import { fetchMobileMoneyOperators } from "@/lib/api/payments";
import {
  normalizeMobileMoneyPhone,
  validateMobileMoneyPhone,
  type MobileMoneyOperator,
  type PaymentStep,
} from "@/lib/mobileMoney";
import { formatPrice } from "@/lib/formatPrice";
import { useAuthStore } from "@/stores/authStore";
import { useCartStore } from "@/stores/cartStore";
import type { Order, PickupPoint } from "@/types/order";

const PAYMENT_POLL_INTERVAL_MS = 2000;

/**
 * Page de passage de commande et paiement.
 */
export default function CheckoutPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const resumeOrderNumber = searchParams.get("order");
  const cart = useCartStore((state) => state.cart);
  const initCart = useCartStore((state) => state.initCart);
  const token = useAuthStore((state) => state.token);
  const user = useAuthStore((state) => state.user);
  const isReady = useAuthStore((state) => state.isReady);
  const isResumeMode = Boolean(resumeOrderNumber);

  const [pickupPoints, setPickupPoints] = useState<PickupPoint[]>([]);
  const [pickupPointsError, setPickupPointsError] = useState<string | null>(null);
  const [shippingConfig, setShippingConfig] = useState<ShippingConfig | null>(null);
  const [fulfillmentType, setFulfillmentType] = useState<"delivery" | "pickup">("pickup");
  const [pickupPointId, setPickupPointId] = useState("");
  const [isInternational, setIsInternational] = useState(false);
  const [internationalCountryCode, setInternationalCountryCode] = useState("FR");
  const [street, setStreet] = useState("");
  const [city, setCity] = useState("");
  const [commune, setCommune] = useState("");
  const [phone, setPhone] = useState("");
  const [paymentPhone, setPaymentPhone] = useState("");
  const [mobileOperators, setMobileOperators] = useState<MobileMoneyOperator[]>([]);
  const [selectedProvider, setSelectedProvider] = useState("");
  const [phoneValidationError, setPhoneValidationError] = useState<string | null>(null);
  const [channel, setChannel] = useState<"mobile_money" | "card">("mobile_money");
  const [shippingQuote, setShippingQuote] = useState<ShippingQuote | null>(null);
  const [shippingError, setShippingError] = useState<string | null>(null);
  const [order, setOrder] = useState<Order | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [polling, setPolling] = useState(false);
  const [paymentSteps, setPaymentSteps] = useState<PaymentStep[]>([]);
  const [paymentMessage, setPaymentMessage] = useState<string | null>(null);
  const previousCityRef = useRef("");

  const selectedOperator = useMemo(
    () => mobileOperators.find((operator) => operator.code === selectedProvider) ?? null,
    [mobileOperators, selectedProvider],
  );

  const hasPhysical = useMemo(() => {
    if (isResumeMode && order) {
      return order.items.some(
        (item) => item.formatType !== "ebook" && item.formatType !== "audiobook",
      );
    }

    return cart?.items.some((item) => !item.format.isDigital) ?? false;
  }, [cart, order, isResumeMode]);

  const deliverableCities = useMemo(
    () => shippingConfig?.cities.filter((item) => item.isDeliveryAvailable) ?? [],
    [shippingConfig],
  );

  const communeOptions = useMemo(() => {
    if (!shippingConfig || shippingConfig.pricingMode !== "zone" || city.trim() === "") {
      return [];
    }

    const normalizedCity = city.trim().toLowerCase();

    return shippingConfig.zones
      .filter((zone) => (zone.cityName ?? "").trim().toLowerCase() === normalizedCity)
      .flatMap((zone) =>
        zone.communes.map((item) => ({
          value: item.name,
          label: item.name,
          zoneName: zone.name,
        })),
      );
  }, [shippingConfig, city]);

  const shippingAmount = fulfillmentType === "delivery" ? (shippingQuote?.amount ?? 0) : 0;

  useEffect(() => {
    void initCart();
    void fetchPickupPoints()
      .then((points) => {
        setPickupPoints(points);
        setPickupPointsError(null);
      })
      .catch(() => {
        setPickupPoints([]);
        setPickupPointsError("Impossible de charger les points de retrait. Vérifiez que l'API est démarrée.");
      });
    void fetchShippingConfig()
      .then((config) => {
        setShippingConfig(config);
        const firstCity = config.cities.find((item) => item.isDeliveryAvailable);

        if (firstCity) {
          setCity(firstCity.name);
        }
      })
      .catch(() => {});
    void fetchMobileMoneyOperators()
      .then((operators) => {
        setMobileOperators(operators);
        if (operators.length > 0) {
          setSelectedProvider(operators[0].code);
        }
      })
      .catch(() => {});
  }, [initCart]);

  useEffect(() => {
    if (!isReady) {
      return;
    }

    if (!token) {
      router.replace("/connexion?redirect=/checkout");
    }
  }, [isReady, token, router]);

  useEffect(() => {
    if (!token || !resumeOrderNumber) {
      return;
    }

    void fetchOrder(token, resumeOrderNumber).then((loaded) => {
      if (loaded.status === "pending_payment") {
        setOrder(loaded);
      }
    });
  }, [token, resumeOrderNumber]);

  useEffect(() => {
    if (!user?.deliveryAddress) {
      return;
    }

    const address = user.deliveryAddress;

    if (address.street) {
      setStreet(address.street);
    }

    if (address.city) {
      setCity(address.city);
    }

    if (address.commune) {
      setCommune(address.commune);
    }

    if (address.phone) {
      setPhone(address.phone);
    }
  }, [user]);

  useEffect(() => {
    if (previousCityRef.current !== "" && previousCityRef.current !== city) {
      setCommune("");
    }

    previousCityRef.current = city;
  }, [city]);

  useEffect(() => {
    if (!hasPhysical || fulfillmentType !== "delivery" || !shippingConfig) {
      setShippingQuote(null);
      setShippingError(null);
      return;
    }

    const country = isInternational
      ? internationalCountryCode.trim().toUpperCase()
      : shippingConfig.domesticCountryCode;

    if (!isInternational && shippingConfig.cities.length > 0 && city.trim() === "") {
      setShippingQuote(null);
      setShippingError(null);
      return;
    }

    if (!isInternational && shippingConfig.pricingMode === "zone" && commune.trim() === "") {
      setShippingQuote(null);
      setShippingError(null);
      return;
    }

    if (isInternational && shippingConfig.internationalPolicy === "unavailable") {
      setShippingQuote(null);
      setShippingError(
        shippingConfig.internationalMessage
          ?? "La livraison hors du pays n'est pas disponible.",
      );
      return;
    }

    const timer = setTimeout(() => {
      void fetchShippingQuote({
        fulfillmentType: "delivery",
        country,
        city: city.trim() || undefined,
        commune: commune.trim() || undefined,
      })
        .then((quote) => {
          setShippingQuote(quote);
          setShippingError(null);
        })
        .catch((err) => {
          setShippingQuote(null);
          setShippingError(err instanceof Error ? err.message : "Frais de livraison indisponibles");
        });
    }, 400);

    return () => clearTimeout(timer);
  }, [
    hasPhysical,
    fulfillmentType,
    shippingConfig,
    isInternational,
    city,
    commune,
    internationalCountryCode,
  ]);

  /**
   * Crée la commande puis lance le paiement.
   */
  const handleCheckout = async (event: React.FormEvent) => {
    event.preventDefault();

    if (!token || !cart) {
      return;
    }

    if (!isResumeMode && hasPhysical && fulfillmentType === "delivery") {
      if (!shippingQuote) {
        setError(shippingError ?? "Impossible de calculer les frais de livraison.");
        return;
      }
    }

    if (channel === "mobile_money") {
      const phoneError = validateMobileMoneyPhone(paymentPhone, selectedOperator);

      if (!selectedProvider) {
        setError("Sélectionnez votre opérateur Mobile Money.");
        return;
      }

      if (phoneError) {
        setPhoneValidationError(phoneError);
        setError(phoneError);
        return;
      }
    }

    setIsLoading(true);
    setError(null);
    setPaymentSteps([
      { id: "order", label: "Enregistrement de la commande", status: "active" },
      { id: "request", label: "Envoi de la demande à votre opérateur", status: "pending" },
      { id: "confirm", label: "Confirmation sur votre téléphone", status: "pending" },
      { id: "verify", label: "Vérification du paiement", status: "pending" },
    ]);
    setPaymentMessage(null);

    try {
      let activeOrder = order;

      if (!activeOrder) {
        const payload: Parameters<typeof createOrder>[1] = {
          notes: undefined,
        };

        if (hasPhysical) {
          payload.fulfillmentType = fulfillmentType;

          if (fulfillmentType === "pickup") {
            payload.pickupPointId = pickupPointId;
          } else if (shippingConfig) {
            payload.shippingAddress = {
              street,
              city,
              commune: commune || undefined,
              country: isInternational
                ? internationalCountryCode.trim().toUpperCase()
                : shippingConfig.domesticCountryCode,
              phone,
            };
          }
        }

        const created = await createOrder(token, payload);
        activeOrder = created.data;
        setOrder(activeOrder);
      }

      setPaymentSteps((steps) =>
        steps.map((step) =>
          step.id === "order"
            ? { ...step, status: "done" }
            : step.id === "request"
              ? { ...step, status: "active" }
              : step,
        ),
      );

      const payment = await initiatePayment(
        token,
        activeOrder.orderNumber,
        channel,
        channel === "mobile_money" ? paymentPhone : undefined,
        channel === "mobile_money" ? selectedProvider : undefined,
      );

      if (payment.data.type === "card" && payment.data.redirectUrl) {
        window.location.href = payment.data.redirectUrl;
        return;
      }

      setPaymentSteps(payment.data.steps ?? []);
      setPaymentMessage(payment.data.message ?? "Validez le paiement sur votre téléphone.");
      setPolling(true);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Erreur lors du paiement");
      setPaymentSteps((steps) =>
        steps.map((step) =>
          step.status === "active" ? { ...step, status: "error" } : step,
        ),
      );
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    if (!polling || !order) {
      return;
    }

    let attempts = 0;
    const maxAttempts = 45;

    const pollPayment = async () => {
      attempts += 1;

      try {
        const result = await checkPaymentStatus(order.orderNumber);

        if (result.data.steps) {
          setPaymentSteps(result.data.steps);
        }

        if (result.data.message) {
          setPaymentMessage(result.data.message);
        }

        if (result.data.status === 0 || (result.data.success && result.data.status !== 2)) {
          setPolling(false);
          setPaymentSteps([
            { id: "order", label: "Commande enregistrée", status: "done" },
            { id: "request", label: "Demande envoyée à votre opérateur", status: "done" },
            { id: "confirm", label: "Confirmation sur votre téléphone", status: "done" },
            { id: "verify", label: "Paiement confirmé", status: "done" },
          ]);
          router.push(`/checkout/result?reference=${order.orderNumber}&status=success`);
          return;
        }

        if (result.data.status === 1) {
          setPolling(false);
          setError(`${result.data.message} Vous pouvez réessayer le paiement ci-dessous.`);
          return;
        }
      } catch {
        // Continue le polling
      }

      if (attempts >= maxAttempts) {
        setPolling(false);
        setError("Délai dépassé. Si vous avez validé sur votre téléphone, vérifiez vos commandes dans quelques minutes.");
      }
    };

    void pollPayment();
    const interval = setInterval(() => {
      void pollPayment();
    }, PAYMENT_POLL_INTERVAL_MS);

    return () => clearInterval(interval);
  }, [polling, order, router]);

  if (!isReady || !token || !user) {
    return <p className="py-20 text-center text-stone-600">Redirection vers la connexion...</p>;
  }

  if (!isResumeMode && (!cart || cart.items.length === 0) && !order) {
    return (
      <div className="py-20 text-center">
        <p className="text-stone-600">Votre panier est vide.</p>
        <Link href="/livres" className="mt-4 inline-block text-amber-700 hover:underline">
          Voir les livres
        </Link>
      </div>
    );
  }

  return (
    <div className="grid gap-8 lg:grid-cols-[2fr_1fr]">
      <form onSubmit={handleCheckout} className="space-y-6">
        <h1 className="text-2xl font-bold text-stone-900">
          {isResumeMode ? "Reprendre le paiement" : "Finaliser la commande"}
        </h1>
        {isResumeMode && order && (
          <p className="text-sm text-amber-700">
            Commande {order.orderNumber} — votre panier est conservé tant que le paiement n&apos;est pas confirmé.
          </p>
        )}
        <p className="text-sm text-stone-600">Connecté en tant que {user.fullName}</p>

        {error && <p className="text-sm text-red-600">{error}</p>}

        {hasPhysical && !isResumeMode && (
          <section className="rounded-xl border border-stone-200 bg-white p-5">
            <h2 className="font-semibold text-stone-900">Mode de réception</h2>
            <div className="mt-4 flex gap-3">
              <button
                type="button"
                onClick={() => setFulfillmentType("pickup")}
                className={`rounded-lg px-4 py-2 text-sm ${
                  fulfillmentType === "pickup" ? "bg-amber-600 text-white" : "bg-stone-100"
                }`}
              >
                Retrait sur place
              </button>
              <button
                type="button"
                onClick={() => setFulfillmentType("delivery")}
                className={`rounded-lg px-4 py-2 text-sm ${
                  fulfillmentType === "delivery" ? "bg-amber-600 text-white" : "bg-stone-100"
                }`}
              >
                Livraison
              </button>
            </div>

            {fulfillmentType === "pickup" ? (
              <div>
                {pickupPointsError && (
                  <p className="text-sm text-red-600">{pickupPointsError}</p>
                )}
                <select
                  required
                  value={pickupPointId}
                  onChange={(event) => setPickupPointId(event.target.value)}
                  className="mt-4 w-full rounded-lg border border-stone-300 px-4 py-2"
                >
                  <option value="">Choisir un point de retrait</option>
                  {pickupPoints.map((point) => (
                    <option key={point.id} value={point.id}>
                      {point.name} — {point.city}
                    </option>
                  ))}
                </select>
              </div>
            ) : (
              <div className="mt-4 space-y-3">
                <div className="flex gap-3">
                  <button
                    type="button"
                    onClick={() => setIsInternational(false)}
                    className={`rounded-lg px-4 py-2 text-sm ${
                      !isInternational ? "bg-amber-600 text-white" : "bg-stone-100"
                    }`}
                  >
                    {shippingConfig?.domesticCountryName ?? "National"}
                  </button>
                  <button
                    type="button"
                    onClick={() => setIsInternational(true)}
                    className={`rounded-lg px-4 py-2 text-sm ${
                      isInternational ? "bg-amber-600 text-white" : "bg-stone-100"
                    }`}
                  >
                    Hors pays
                  </button>
                </div>

                <input
                  required
                  placeholder="Adresse"
                  value={street}
                  onChange={(event) => setStreet(event.target.value)}
                  className="w-full rounded-lg border border-stone-300 px-4 py-2"
                />
                {!isInternational && deliverableCities.length > 0 ? (
                  <select
                    required
                    value={city}
                    onChange={(event) => setCity(event.target.value)}
                    className="w-full rounded-lg border border-stone-300 px-4 py-2"
                  >
                    <option value="">Choisir une ville</option>
                    {deliverableCities.map((item) => (
                      <option key={item.id} value={item.name}>
                        {item.name}
                      </option>
                    ))}
                  </select>
                ) : (
                  <input
                    required
                    placeholder="Ville"
                    value={city}
                    onChange={(event) => setCity(event.target.value)}
                    className="w-full rounded-lg border border-stone-300 px-4 py-2"
                  />
                )}

                {!isInternational && shippingConfig?.cities.length > 0 && deliverableCities.length === 0 && (
                  <p className="text-sm text-red-600">
                    Aucune ville n&apos;est ouverte à la livraison pour le moment.
                  </p>
                )}

                {!isInternational && shippingConfig?.pricingMode === "zone" ? (
                  <select
                    required
                    value={commune}
                    onChange={(event) => setCommune(event.target.value)}
                    className="w-full rounded-lg border border-stone-300 px-4 py-2"
                  >
                    <option value="">Choisir une commune</option>
                    {communeOptions.map((option) => (
                      <option key={`${option.value}-${option.label}`} value={option.value}>
                        {option.label} — {option.zoneName}
                      </option>
                    ))}
                  </select>
                ) : (
                  !isInternational && (
                    <input
                      placeholder="Commune (optionnel en mode fixe)"
                      value={commune}
                      onChange={(event) => setCommune(event.target.value)}
                      className="w-full rounded-lg border border-stone-300 px-4 py-2"
                    />
                  )
                )}

                {isInternational && (
                  <input
                    required
                    placeholder="Code pays (ex. FR, BE, US)"
                    maxLength={2}
                    value={internationalCountryCode}
                    onChange={(event) => setInternationalCountryCode(event.target.value.toUpperCase())}
                    className="w-full rounded-lg border border-stone-300 px-4 py-2 uppercase"
                  />
                )}

                <input
                  required
                  placeholder="Téléphone livraison (243...)"
                  value={phone}
                  onChange={(event) => setPhone(event.target.value)}
                  className="w-full rounded-lg border border-stone-300 px-4 py-2"
                />

                {shippingError && (
                  <p className="text-sm text-red-600">{shippingError}</p>
                )}
                {shippingQuote?.policyMessage && (
                  <p className="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">
                    {shippingQuote.policyMessage}
                  </p>
                )}
                {shippingQuote && (
                  <p className="text-sm text-stone-600">
                    {shippingQuote.label}
                    {shippingQuote.requiresQuote
                      ? " — frais confirmés après contact"
                      : ` : ${formatPrice(shippingQuote.amount, shippingQuote.currency)}`}
                  </p>
                )}
              </div>
            )}
          </section>
        )}

        <section className="rounded-xl border border-stone-200 bg-white p-5">
          <h2 className="font-semibold text-stone-900">Paiement</h2>
          <div className="mt-4 flex gap-3">
            <button
              type="button"
              onClick={() => setChannel("mobile_money")}
              className={`rounded-lg px-4 py-2 text-sm ${
                channel === "mobile_money" ? "bg-amber-600 text-white" : "bg-stone-100"
              }`}
            >
              Mobile Money
            </button>
            <button
              type="button"
              onClick={() => setChannel("card")}
              className={`rounded-lg px-4 py-2 text-sm ${
                channel === "card" ? "bg-amber-600 text-white" : "bg-stone-100"
              }`}
            >
              Carte bancaire
            </button>
          </div>

          {channel === "mobile_money" && (
            <div className="mt-4 space-y-4">
              <div>
                <p className="text-sm font-medium text-stone-700">Choisissez votre opérateur</p>
                <div className="mt-2 grid gap-2 sm:grid-cols-2">
                  {mobileOperators.map((operator) => (
                    <label
                      key={operator.code}
                      className={`flex cursor-pointer items-center gap-3 rounded-lg border px-4 py-3 ${
                        selectedProvider === operator.code
                          ? "border-amber-600 bg-amber-50"
                          : "border-stone-200"
                      }`}
                    >
                      <input
                        type="radio"
                        name="mobile-provider"
                        checked={selectedProvider === operator.code}
                        onChange={() => {
                          setSelectedProvider(operator.code);
                          setPhoneValidationError(
                            validateMobileMoneyPhone(paymentPhone, operator),
                          );
                        }}
                      />
                      <span>
                        <span className="block font-medium text-stone-900">{operator.label}</span>
                        <span className="text-xs text-stone-500">{operator.phoneHint}</span>
                      </span>
                    </label>
                  ))}
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium text-stone-700">
                  Numéro Mobile Money
                </label>
                <input
                  required
                  placeholder={selectedOperator?.phoneHint ?? "243XXXXXXXXX"}
                  value={paymentPhone}
                  onChange={(event) => {
                    const normalized = normalizeMobileMoneyPhone(event.target.value);
                    setPaymentPhone(normalized);
                    setPhoneValidationError(
                      validateMobileMoneyPhone(normalized, selectedOperator),
                    );
                  }}
                  className="mt-1 w-full rounded-lg border border-stone-300 px-4 py-2"
                />
                {phoneValidationError && (
                  <p className="mt-1 text-sm text-red-600">{phoneValidationError}</p>
                )}
              </div>
            </div>
          )}
        </section>

        <button
          type="submit"
          disabled={
            isLoading
            || polling
            || (!isResumeMode && hasPhysical && fulfillmentType === "delivery" && !shippingQuote)
            || (channel === "mobile_money" && Boolean(phoneValidationError))
          }
          className="w-full rounded-lg bg-amber-600 px-8 py-3 text-sm font-semibold text-white disabled:opacity-60"
        >
          {isLoading ? "Traitement..." : polling ? "Paiement en cours..." : "Payer maintenant"}
        </button>

        {(paymentSteps.length > 0 || paymentMessage) && (
          <PaymentSteps steps={paymentSteps} message={paymentMessage} />
        )}
      </form>

      <aside className="h-fit rounded-xl border border-stone-200 bg-white p-6">
        <h2 className="text-lg font-semibold">Récapitulatif</h2>
        <dl className="mt-4 space-y-2 text-sm">
          {isResumeMode && order ? (
            <>
              {order.items.map((item) => (
                <div key={item.id} className="flex justify-between">
                  <dt>{item.bookTitle} × {item.quantity}</dt>
                  <dd>{formatPrice(item.totalPrice, order.currency)}</dd>
                </div>
              ))}
              {order.shippingAmount > 0 && (
                <div className="flex justify-between">
                  <dt>Livraison</dt>
                  <dd>{formatPrice(order.shippingAmount, order.currency)}</dd>
                </div>
              )}
              <div className="flex justify-between border-t border-stone-200 pt-3 font-semibold">
                <dt>Total</dt>
                <dd>{formatPrice(order.total, order.currency)}</dd>
              </div>
            </>
          ) : cart ? (
            <>
              <div className="flex justify-between">
                <dt>Sous-total</dt>
                <dd>{formatPrice(cart.summary.subtotal, cart.summary.currency)}</dd>
              </div>
              {cart.summary.discount.amount > 0 && (
                <div className="flex justify-between text-green-700">
                  <dt>Remise</dt>
                  <dd>-{formatPrice(cart.summary.discount.amount, cart.summary.currency)}</dd>
                </div>
              )}
              {hasPhysical && fulfillmentType === "delivery" && (
                <div className="flex justify-between">
                  <dt>Livraison</dt>
                  <dd>
                    {shippingQuote
                      ? shippingQuote.requiresQuote
                        ? "Sur devis"
                        : formatPrice(shippingAmount, shippingQuote.currency)
                      : "—"}
                  </dd>
                </div>
              )}
              <div className="flex justify-between border-t border-stone-200 pt-3 font-semibold">
                <dt>Total estimé</dt>
                <dd>
                  {formatPrice(
                    cart.summary.total + shippingAmount,
                    cart.summary.currency,
                  )}
                </dd>
              </div>
            </>
          ) : null}
        </dl>
      </aside>
    </div>
  );
}
