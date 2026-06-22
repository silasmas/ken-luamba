<?php

namespace App\Enums;

/**
 * Type d'invité pour un événement (VIP, VVIP, Autre).
 */
enum InvitationGuestType: string
{
  case Vip = 'vip';
  case Vvip = 'vvip';
  case Other = 'other';

  /**
   * Libellé affiché dans l'interface et sur la page publique.
   *
   * @return string Libellé du type d'invité
   */
  public function label(): string
  {
    return match ($this) {
      self::Vip => 'VIP',
      self::Vvip => 'VVIP',
      self::Other => 'Autre',
    };
  }

  /**
   * Retourne le libellé d'une valeur stockée en base.
   *
   * @param string|null $value Valeur brute (vip, vvip, other)
   * @return string|null Libellé ou null
   */
  public static function labelFor(?string $value): ?string
  {
    if ($value === null || $value === '') {
      return null;
    }

    return self::tryFrom($value)?->label() ?? $value;
  }

  /**
   * Normalise une valeur saisie (Excel ou formulaire) vers la valeur enum.
   *
   * @param string|null $value Texte saisi
   * @return string|null Valeur enum ou null si invalide
   */
  public static function normalizeImport(?string $value): ?string
  {
    if ($value === null || trim($value) === '') {
      return null;
    }

    $upper = mb_strtoupper(trim($value));

    return match ($upper) {
      'VIP' => self::Vip->value,
      'VVIP' => self::Vvip->value,
      'AUTRE', 'AUTRES', 'OTHER' => self::Other->value,
      default => self::tryFrom(mb_strtolower(trim($value)))?->value,
    };
  }
}
