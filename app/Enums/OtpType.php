<?php

namespace App\Enums;

/**
 * Types de codes OTP (inscription ou connexion).
 */
enum OtpType: string
{
  case Register = 'register';
  case Login = 'login';

  /**
   * Libellé affiché dans les logs ou l'admin.
   *
   * @return string Libellé du type OTP
   */
  public function label(): string
  {
    return match ($this) {
      self::Register => 'Inscription',
      self::Login => 'Connexion',
    };
  }
}
