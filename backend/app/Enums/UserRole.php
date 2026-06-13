<?php

namespace App\Enums;

/**
 * Rôles utilisateurs de la plateforme.
 */
enum UserRole: string
{
  case Client = 'client';
  case Courier = 'courier';
  case Editor = 'editor';
  case Admin = 'admin';

  /**
   * Libellé affiché dans l'interface admin.
   *
   * @return string Libellé traduit du rôle
   */
  public function label(): string
  {
    return match ($this) {
      self::Client => 'Client',
      self::Courier => 'Livreur',
      self::Editor => 'Éditeur',
      self::Admin => 'Administrateur',
    };
  }
}
