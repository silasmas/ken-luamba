<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email envoyé au client après un paiement réussi.
 */
class OrderPaymentSuccessNotification extends Notification
{
  use Queueable;

  /**
   * Initialise la notification de paiement réussi.
   *
   * @param Order $order Commande payée
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
   * Construit l'email de confirmation de paiement.
   *
   * @param mixed $notifiable Destinataire
   * @return MailMessage Message email
   */
  public function toMail(mixed $notifiable): MailMessage
  {
    $frontendUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');
    $orderUrl = $frontendUrl.'/espace/commandes/'.$this->order->order_number;

    return (new MailMessage)
      ->subject('Paiement confirmé — '.$this->order->order_number)
      ->greeting('Bonjour '.$notifiable->full_name.',')
      ->line('Votre paiement pour la commande **'.$this->order->order_number.'** a été confirmé.')
      ->line('Montant : **'.number_format((float) $this->order->total, 0, ',', ' ').' '.$this->order->currency.'**')
      ->action('Voir ma commande', $orderUrl)
      ->line('Merci pour votre confiance !');
  }
}
