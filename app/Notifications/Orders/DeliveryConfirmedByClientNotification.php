<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email envoyé après confirmation de réception par le client.
 */
class DeliveryConfirmedByClientNotification extends Notification
{
  use Queueable;

  /**
   * Initialise la notification de réception confirmée par le client.
   *
   * @param Order $order Commande terminée
   * @param User|null $courier Livreur concerné
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
   * Construit l'email de confirmation client.
   *
   * @param mixed $notifiable Destinataire
   * @return MailMessage Message email
   */
  public function toMail(mixed $notifiable): MailMessage
  {
    $frontendUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');
    $isAdmin = $notifiable->role?->value === 'admin';
    $clientName = $this->order->user?->full_name ?? 'le client';
    $courierName = $this->courier?->full_name ?? 'le livreur';

    if ($isAdmin) {
      return (new MailMessage)
        ->subject('Commande terminée — '.$this->order->order_number)
        ->greeting('Bonjour,')
        ->line('La commande **'.$this->order->order_number.'** est terminée.')
        ->line('Client : **'.$clientName.'** — Livreur : **'.$courierName.'**');
    }

    if ($notifiable->id === $this->courier?->id) {
      return (new MailMessage)
        ->subject('Réception confirmée — '.$this->order->order_number)
        ->greeting('Bonjour '.$notifiable->full_name.',')
        ->line('**'.$clientName.'** a confirmé la réception de **'.$this->order->order_number.'**.')
        ->line('Merci pour votre intervention !')
        ->action('Espace livreur', $frontendUrl.'/livreur');
    }

    return (new MailMessage)
      ->subject('Merci pour votre confirmation — '.$this->order->order_number)
      ->greeting('Bonjour '.$notifiable->full_name.',')
      ->line('Votre réception de la commande **'.$this->order->order_number.'** est enregistrée.')
      ->action('Voir ma commande', $frontendUrl.'/espace/commandes/'.$this->order->order_number)
      ->line('Merci pour votre confiance !');
  }
}
