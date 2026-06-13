"use client";

import { useRef } from "react";

interface PhotoProofInputProps {
  /** Fichier photo sélectionné */
  photo: File | null;
  /**
   * Met à jour la photo sélectionnée.
   *
   * @param file Fichier image ou null
   */
  onPhotoChange: (file: File | null) => void;
  /** Indique si la photo est obligatoire */
  required?: boolean;
}

/**
 * Saisie de photo preuve : prise via caméra ou import depuis la galerie.
 */
export function PhotoProofInput({ photo, onPhotoChange, required = false }: PhotoProofInputProps) {
  const cameraInputRef = useRef<HTMLInputElement>(null);
  const galleryInputRef = useRef<HTMLInputElement>(null);

  /**
   * Traite la sélection d'un fichier image.
   *
   * @param event Événement change de l'input file
   */
  const handleFileChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0] ?? null;
    onPhotoChange(file);
    event.target.value = "";
  };

  return (
    <div className="space-y-3">
      <label className="block text-sm font-medium text-stone-700">
        Photo preuve {required ? "(obligatoire)" : "(recommandée)"}
      </label>

      <div className="flex flex-wrap gap-2">
        <button
          type="button"
          onClick={() => cameraInputRef.current?.click()}
          className="rounded-lg bg-stone-800 px-4 py-2 text-sm text-white"
        >
          Ouvrir l&apos;appareil photo
        </button>
        <button
          type="button"
          onClick={() => galleryInputRef.current?.click()}
          className="rounded-lg border border-stone-300 px-4 py-2 text-sm text-stone-700"
        >
          Choisir une photo
        </button>
        {photo && (
          <button
            type="button"
            onClick={() => onPhotoChange(null)}
            className="rounded-lg px-4 py-2 text-sm text-red-600 hover:bg-red-50"
          >
            Supprimer
          </button>
        )}
      </div>

      <input
        ref={cameraInputRef}
        type="file"
        accept="image/*"
        capture="environment"
        className="hidden"
        onChange={handleFileChange}
      />
      <input
        ref={galleryInputRef}
        type="file"
        accept="image/*"
        className="hidden"
        onChange={handleFileChange}
      />

      {photo && (
        <div className="space-y-2">
          <p className="text-xs text-stone-500">{photo.name}</p>
          <img
            src={URL.createObjectURL(photo)}
            alt="Aperçu photo preuve"
            className="max-h-48 rounded-lg border border-stone-200 object-contain"
          />
        </div>
      )}
    </div>
  );
}
