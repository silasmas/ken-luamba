import type { OrderCourier } from "@/types/order";

interface CourierInfoCardProps {
  /** Informations du livreur assigné */
  courier: OrderCourier;
}

/**
 * Carte affichant le livreur assigné à une commande.
 */
export function CourierInfoCard({ courier }: CourierInfoCardProps) {
  return (
    <section className="rounded-xl border border-stone-200 bg-white p-5">
      <h2 className="font-semibold text-stone-900">Votre livreur</h2>
      <div className="mt-3 flex items-center gap-4">
        {courier.avatarUrl ? (
          <img
            src={courier.avatarUrl}
            alt={courier.fullName}
            className="h-14 w-14 rounded-full object-cover"
          />
        ) : (
          <div className="flex h-14 w-14 items-center justify-center rounded-full bg-stone-200 text-lg font-semibold text-stone-600">
            {courier.fullName.charAt(0).toUpperCase()}
          </div>
        )}
        <div>
          <p className="font-medium text-stone-900">{courier.fullName}</p>
          {courier.phone && (
            <p className="text-sm text-stone-500">{courier.phone}</p>
          )}
        </div>
      </div>
    </section>
  );
}
