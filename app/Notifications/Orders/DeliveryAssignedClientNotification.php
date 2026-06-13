<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email envoyé au client lors de l'assignation d'un livreur.
 */
class DeliveryAssignedClientNotification extends Notification
{
  use Queueable;

  /**
   * Initialise la notification d'assignation pour le client.
   *
   * @param Order $order Commande concernée
   * @param User|null $courier Livreur assigné
   */
  public function __construct(
    private readonly Order $order,
    private readonly ?User $courier,
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
   * Construit l'email d'assignation livreur pour le client.
   *
   * @param mixed $notifiable Destinataire
   * @return MailMessage Message email
   */
  public function toMail(mixed $notifiable): MailMessage
  {
    $frontendUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');
    $orderUrl = $frontendUrl.'/espace/commandes/'.$this->order->order_number;
    $courierName = $this->courier?->full_name ?? 'un livreur';
    $courierPhone = $this->courier?->phone;

    $message = (new MailMessage)
      ->subject('Livreur assigné — '.$this->order->order_number)
      ->greeting('Bonjour '.$notifiable->full_name.',')
      ->line('Votre commande **'.$this->order->order_number.'** est en cours de livraison.')
      ->line('**'.$courierName.'** va vous livrer.');

    if ($courierPhone) {
      $message->line('Téléphone du livreur : **'.$courierPhone.'**');
    }

    return $message
      ->action('Suivre ma commande', $orderUrl)
      ->line('Présentez votre QR code au livreur lors de la remise.');
  }
}
