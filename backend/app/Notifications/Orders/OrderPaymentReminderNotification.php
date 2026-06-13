<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Rappel email au client pour une commande en attente de paiement (après 5 h).
 */
class OrderPaymentReminderNotification extends Notification
{
  use Queueable;

  /**
   * Initialise le rappel de paiement.
   *
   * @param Order $order Commande en attente
   */
  public function __construct(private readonly Order $order) {}

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
   * Construit l'email de rappel de paiement.
   *
   * @param mixed $notifiable Destinataire
   * @return MailMessage Message email
   */
  public function toMail(mixed $notifiable): MailMessage
  {
    $frontendUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');
    $orderUrl = $frontendUrl.'/espace/commandes/'.$this->order->order_number;

    return (new MailMessage)
      ->subject('Rappel : finalisez votre paiement — '.$this->order->order_number)
      ->greeting('Bonjour '.$notifiable->full_name.',')
      ->line('Votre commande **'.$this->order->order_number.'** attend toujours un paiement.')
      ->line('Montant : **'.number_format((float) $this->order->total, 0, ',', ' ').' '.$this->order->currency.'**')
      ->action('Reprendre le paiement', $orderUrl)
      ->line('Sans paiement, la commande pourra être annulée.');
  }
}
