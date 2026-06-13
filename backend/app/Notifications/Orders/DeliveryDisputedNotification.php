<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email envoyé au livreur et à l'admin quand le client conteste une livraison.
 */
class DeliveryDisputedNotification extends Notification
{
  use Queueable;

  /**
   * Initialise la notification de litige livraison.
   *
   * @param Order $order Commande en litige
   * @param User|null $courier Livreur concerné
   * @param string|null $reason Motif du client
   */
  public function __construct(
    private readonly Order $order,
    private readonly ?User $courier,
    private readonly ?string $reason,
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
   * Construit l'email de litige livraison.
   *
   * @param mixed $notifiable Destinataire
   * @return MailMessage Message email
   */
  public function toMail(mixed $notifiable): MailMessage
  {
    $clientName = $this->order->user?->full_name ?? 'le client';
    $courierName = $this->courier?->full_name ?? '—';
    $isAdmin = $notifiable->role?->value === 'admin';
    $reason = $this->reason ?? 'Le client indique ne pas avoir reçu sa commande.';

    if ($isAdmin) {
      return (new MailMessage)
        ->subject('Litige livraison — '.$this->order->order_number)
        ->greeting('Bonjour,')
        ->line('Un client a contesté la livraison de la commande **'.$this->order->order_number.'**.')
        ->line('Client : **'.$clientName.'**')
        ->line('Livreur assigné : **'.$courierName.'**')
        ->line('Motif : '.$reason)
        ->line('Vérifiez le dossier depuis l\'administration.');
    }

    return (new MailMessage)
      ->subject('Litige signalé — '.$this->order->order_number)
      ->greeting('Bonjour '.$notifiable->full_name.',')
      ->line('**'.$clientName.'** conteste la livraison de la commande **'.$this->order->order_number.'**.')
      ->line('Motif : '.$reason)
      ->line('Notre équipe va examiner le dossier. Merci de rester disponible.');
  }
}
