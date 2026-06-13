<?php

namespace App\Notifications;

use App\Enums\OtpType;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification email contenant le code OTP.
 */
class OtpCodeNotification extends Notification
{
  use Queueable;

  /**
   * Initialise la notification OTP.
   *
   * @param string $plainCode Code OTP en clair
   * @param OtpType $type Type d'OTP (inscription ou connexion)
   */
  public function __construct(
    private readonly string $plainCode,
    private readonly OtpType $type,
  ) {}

  /**
   * Canaux de diffusion de la notification.
   *
   * @param mixed $notifiable Destinataire
   * @return list<string> Canaux utilisés
   */
  public function via(mixed $notifiable): array
  {
    return ['mail'];
  }

  /**
   * Construit l'email OTP.
   *
   * @param mixed $notifiable Destinataire
   * @return MailMessage Message email
   */
  public function toMail(mixed $notifiable): MailMessage
  {
    $action = $this->type === OtpType::Register ? 'finaliser votre inscription' : 'vous connecter';

    return (new MailMessage)
      ->subject('Votre code de connexion — Ken Luamba')
      ->greeting('Bonjour,')
      ->line('Utilisez le code ci-dessous pour '.$action.' :')
      ->line('**'.$this->plainCode.'**')
      ->line('Ce code expire dans '.config('otp.expiry_minutes', 10).' minutes.')
      ->line('Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet email.');
  }
}
