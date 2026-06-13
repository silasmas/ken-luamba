"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { EspaceLayout } from "@/components/espace/EspaceLayout";
import { updateAvatar, updateProfile } from "@/lib/api/auth";
import { useAuthStore } from "@/stores/authStore";
import type { UserAddress } from "@/types/auth";

const EMPTY_ADDRESS: UserAddress = {
  street: "",
  city: "",
  commune: "",
  country: "CD",
  phone: "",
};

/**
 * Page de gestion du profil client.
 */
export default function ProfilPage() {
  const token = useAuthStore((state) => state.token);
  const user = useAuthStore((state) => state.user);
  const isReady = useAuthStore((state) => state.isReady);
  const setUser = useAuthStore((state) => state.setUser);

  const [fullName, setFullName] = useState("");
  const [phone, setPhone] = useState("");
  const [profileAddress, setProfileAddress] = useState<UserAddress>(EMPTY_ADDRESS);
  const [deliveryAddress, setDeliveryAddress] = useState<UserAddress>(EMPTY_ADDRESS);
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [isSaving, setIsSaving] = useState(false);

  useEffect(() => {
    if (!user) {
      return;
    }

    setFullName(user.fullName);
    setPhone(user.phone ?? "");
    setProfileAddress({ ...EMPTY_ADDRESS, ...(user.profileAddress ?? {}) });
    setDeliveryAddress({ ...EMPTY_ADDRESS, ...(user.deliveryAddress ?? {}) });
  }, [user]);

  /**
   * Enregistre les modifications du profil.
   */
  const handleSave = async (event: React.FormEvent) => {
    event.preventDefault();

    if (!token) {
      return;
    }

    setIsSaving(true);
    setError(null);
    setMessage(null);

    try {
      const response = await updateProfile(token, {
        fullName,
        phone: phone || null,
        profileAddress,
        deliveryAddress,
      });
      setUser(response.data);
      setMessage(response.message);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Erreur de sauvegarde");
    } finally {
      setIsSaving(false);
    }
  };

  /**
   * Met à jour la photo de profil.
   *
   * @param event Changement de fichier
   */
  const handleAvatarChange = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];

    if (!file || !token) {
      return;
    }

    try {
      const response = await updateAvatar(token, file);
      setUser(response.data);
      setMessage(response.message);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Erreur photo");
    }
  };

  if (!isReady) {
    return (
      <EspaceLayout>
        <p className="py-10 text-center text-stone-600">Chargement...</p>
      </EspaceLayout>
    );
  }

  if (!token || !user) {
    return (
      <EspaceLayout>
        <div className="py-10 text-center">
          <p className="text-stone-600">Connectez-vous pour gérer votre profil.</p>
          <Link href="/connexion?redirect=/espace/profil" className="mt-4 inline-block text-amber-700 hover:underline">
            Se connecter
          </Link>
        </div>
      </EspaceLayout>
    );
  }

  return (
    <EspaceLayout>
      <form onSubmit={handleSave} className="mx-auto max-w-2xl space-y-6">
        <h1 className="text-2xl font-bold text-stone-900">Mon profil</h1>

        {message && <p className="rounded-lg bg-green-50 p-3 text-sm text-green-800">{message}</p>}
        {error && <p className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}

        <section className="rounded-xl border border-stone-200 bg-white p-5">
          <h2 className="font-semibold">Photo</h2>
          <div className="mt-4 flex items-center gap-4">
            {user.avatarUrl ? (
              <img
                src={user.avatarUrl}
                alt={user.fullName}
                className="h-20 w-20 rounded-full object-cover"
              />
            ) : (
              <div className="flex h-20 w-20 items-center justify-center rounded-full bg-stone-200 text-stone-500">
                {user.fullName.charAt(0)}
              </div>
            )}
            <input type="file" accept="image/*" onChange={(event) => void handleAvatarChange(event)} />
          </div>
        </section>

        <section className="rounded-xl border border-stone-200 bg-white p-5 space-y-4">
          <h2 className="font-semibold">Informations</h2>
          <div>
            <label className="block text-sm font-medium">Nom complet</label>
            <input
              required
              value={fullName}
              onChange={(event) => setFullName(event.target.value)}
              className="mt-1 w-full rounded-lg border border-stone-300 px-4 py-2"
            />
          </div>
          <div>
            <label className="block text-sm font-medium">Email</label>
            <input value={user.email} disabled className="mt-1 w-full rounded-lg border border-stone-200 bg-stone-50 px-4 py-2" />
          </div>
          <div>
            <label className="block text-sm font-medium">Téléphone</label>
            <input
              value={phone}
              onChange={(event) => setPhone(event.target.value)}
              placeholder="243..."
              className="mt-1 w-full rounded-lg border border-stone-300 px-4 py-2"
            />
          </div>
        </section>

        <AddressSection
          title="Adresse personnelle"
          address={profileAddress}
          onChange={setProfileAddress}
        />

        <AddressSection
          title="Adresse de livraison par défaut"
          address={deliveryAddress}
          onChange={setDeliveryAddress}
        />

        <button
          type="submit"
          disabled={isSaving}
          className="rounded-lg bg-amber-600 px-8 py-3 text-sm font-semibold text-white disabled:opacity-60"
        >
          {isSaving ? "Enregistrement..." : "Enregistrer"}
        </button>
      </form>
    </EspaceLayout>
  );
}

/**
 * Bloc de saisie d'une adresse.
 *
 * @param title Titre de section
 * @param address Valeurs courantes
 * @param onChange Callback de mise à jour
 */
function AddressSection({
  title,
  address,
  onChange,
}: {
  title: string;
  address: UserAddress;
  onChange: (value: UserAddress) => void;
}) {
  return (
    <section className="rounded-xl border border-stone-200 bg-white p-5 space-y-3">
      <h2 className="font-semibold">{title}</h2>
      <input
        placeholder="Rue / avenue"
        value={address.street ?? ""}
        onChange={(event) => onChange({ ...address, street: event.target.value })}
        className="w-full rounded-lg border border-stone-300 px-4 py-2"
      />
      <input
        placeholder="Ville"
        value={address.city ?? ""}
        onChange={(event) => onChange({ ...address, city: event.target.value })}
        className="w-full rounded-lg border border-stone-300 px-4 py-2"
      />
      <input
        placeholder="Commune"
        value={address.commune ?? ""}
        onChange={(event) => onChange({ ...address, commune: event.target.value })}
        className="w-full rounded-lg border border-stone-300 px-4 py-2"
      />
      <input
        placeholder="Pays (ex. CD)"
        maxLength={2}
        value={address.country ?? "CD"}
        onChange={(event) => onChange({ ...address, country: event.target.value.toUpperCase() })}
        className="w-full rounded-lg border border-stone-300 px-4 py-2 uppercase"
      />
      <input
        placeholder="Téléphone"
        value={address.phone ?? ""}
        onChange={(event) => onChange({ ...address, phone: event.target.value })}
        className="w-full rounded-lg border border-stone-300 px-4 py-2"
      />
    </section>
  );
}
