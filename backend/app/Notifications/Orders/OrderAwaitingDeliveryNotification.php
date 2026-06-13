<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerte admin : commande payée en attente de livraison.
 */
class OrderAwaitingDeliveryNotification extends Notification
{
  use Queueable;

  /**
   * Initialise l'alerte commande en attente de livraison.
   *
   * @param Order $order Commande payée avec livraison physique
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
   * Construit l'email d'alerte admin.
   *
   * @param mixed $notifiable Destinataire admin
   * @return MailMessage Message email
   */
  public function toMail(mixed $notifiable): MailMessage
  {
    $clientName = $this->order->user?->full_name ?? '—';

    return (new MailMessage)
      ->subject('Commande à livrer — '.$this->order->order_number)
      ->greeting('Bonjour,')
      ->line('Une commande payée attend une livraison.')
      ->line('Commande : **'.$this->order->order_number.'**')
      ->line('Client : **'.$clientName.'**')
      ->line('Montant : **'.number_format((float) $this->order->total, 0, ',', ' ').' '.$this->order->currency.'**')
      ->line('Assignez un livreur depuis l\'administration.');
  }
}
