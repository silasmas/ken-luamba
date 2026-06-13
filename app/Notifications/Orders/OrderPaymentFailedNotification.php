<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email envoyé au client après un échec de paiement.
 */
class OrderPaymentFailedNotification extends Notification
{
  use Queueable;

  /**
   * Initialise la notification d'échec de paiement.
   *
   * @param Order $order Commande concernée
   * @param string $reason Motif de l'échec
   */
  public function __construct(
    private readonly Order $order,
    private readonly string $reason,
  ) {}

  /**
   * Canaux de diffusion.
   *
   * @param mixed $notifiable Destinataire
   * @return list<string>
   */
  public function via(mixed $notifiable): array
  {
    return ['mail'];
  }

  /**
   * Construit l'email d'échec de paiement.
   *
   * @param mixed $notifiable Destinataire
   * @return MailMessage Message email
   */
  public function toMail(mixed $notifiable): MailMessage
  {
    $frontendUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');
    $orderUrl = $frontendUrl.'/espace/commandes/'.$this->order->order_number;

    return (new MailMessage)
      ->subject('Paiement non abouti — '.$this->order->order_number)
      ->greeting('Bonjour '.$notifiable->full_name.',')
      ->line('Le paiement de votre commande **'.$this->order->order_number.'** n\'a pas abouti.')
      ->line($this->reason)
      ->action('Reprendre le paiement', $orderUrl)
      ->line('Votre commande reste enregistrée. Vous pouvez réessayer quand vous le souhaitez.');
  }
}
