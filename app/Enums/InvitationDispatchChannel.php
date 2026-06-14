<?php

namespace App\Enums;

/**
 * Canaux d'envoi des invitations (email, SMS, WhatsApp).
 */
enum InvitationDispatchChannel: string
{
  case Email = 'email';
  case Sms = 'sms';
  case Whatsapp = 'whatsapp';

  /**
   * Libellé affiché dans l'interface admin.
   *
   * @return string Libellé du canal
   */
  public function label(): string
  {
    return match ($this) {
      self::Email => 'Email',
      self::Sms => 'SMS',
      self::Whatsapp => 'WhatsApp',
    };
  }
}
