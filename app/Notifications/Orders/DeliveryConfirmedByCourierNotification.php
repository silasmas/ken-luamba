<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email envoyé après confirmation de livraison par le livreur (scan QR).
 */
class DeliveryConfirmedByCourierNotification extends Notification
{
  use Queueable;

  /**
   * Initialise la notification de livraison confirmée par le livreur.
   *
   * @param Order $order Commande livrée
   * @param User|null $courier Livreur ayant confirmé
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
   * Construit l'email de confirmation livreur.
   *
   * @param mixed $notifiable Destinataire
   * @return MailMessage Message email
   */
  public function toMail(mixed $notifiable): MailMessage
  {
    $frontendUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');
    $courierName = $this->courier?->full_name ?? 'Le livreur';
    $isAdmin = $notifiable->role?->value === 'admin';

    $isDirect = $this->order->isDirectPayment();
    $clientEmail = $this->order->user?->email ?? '—';

    if ($isAdmin) {
      $mail = (new MailMessage)
        ->subject('Livraison confirmée par le livreur — '.$this->order->order_number)
        ->greeting('Bonjour,')
        ->line('La commande **'.$this->order->order_number.'** a été marquée livrée par **'.$courierName.'**.')
        ->line('Client : **'.($this->order->user?->full_name ?? '—').'**')
        ->line('Email : **'.$clientEmail.'**');

      return $isDirect
        ? $mail->line('Remise paiement direct finalisée.')
        : $mail->line('En attente de confirmation client.');
    }

    if ($notifiable->id === $this->courier?->id) {
      $mail = (new MailMessage)
        ->subject('Livraison confirmée — '.$this->order->order_number)
        ->greeting('Bonjour '.$notifiable->full_name.',')
        ->line('Vous avez confirmé la livraison de **'.$this->order->order_number.'**.');

      if ($isDirect) {
        return $mail
          ->line('La remise paiement direct est finalisée.')
          ->action('Espace livreur', $frontendUrl.'/livreur/scan');
      }

      return $mail
        ->line('Le client va maintenant confirmer la réception.')
        ->action('Espace livreur', $frontendUrl.'/livreur');
    }

    if ($isDirect) {
      return (new MailMessage)
        ->subject('Livraison effectuée — '.$this->order->order_number)
        ->greeting('Bonjour '.($notifiable->full_name ?? '').',')
        ->line('Votre achat **'.$this->order->order_number.'** a bien été remis.')
        ->line('Merci pour votre confiance !');
    }

    return (new MailMessage)
      ->subject('Votre commande a été livrée — '.$this->order->order_number)
      ->greeting('Bonjour '.$notifiable->full_name.',')
      ->line('**'.$courierName.'** a confirmé la livraison de votre commande **'.$this->order->order_number.'**.')
      ->action('Confirmer la réception', $frontendUrl.'/espace/commandes/'.$this->order->order_number)
      ->line('Si tout est en ordre, confirmez la réception. Sinon, vous pouvez contester.');
  }
}
