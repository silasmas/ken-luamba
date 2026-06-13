<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email envoyé au livreur lors de l'assignation d'une course.
 */
class DeliveryAssignedCourierNotification extends Notification
{
  use Queueable;

  /**
   * Initialise la notification d'assignation pour le livreur.
   *
   * @param Order $order Commande à livrer
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
   * Construit l'email d'assignation pour le livreur.
   *
   * @param mixed $notifiable Destinataire
   * @return MailMessage Message email
   */
  public function toMail(mixed $notifiable): MailMessage
  {
    $frontendUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');
    $livreurUrl = $frontendUrl.'/livreur';
    $clientName = $this->order->user?->full_name ?? 'le client';
    $address = $this->formatAddress();

    $message = (new MailMessage)
      ->subject('Nouvelle course — '.$this->order->order_number)
      ->greeting('Bonjour '.$notifiable->full_name.',')
      ->line('Une nouvelle livraison vous a été assignée.')
      ->line('Commande : **'.$this->order->order_number.'**')
      ->line('Client : **'.$clientName.'**');

    if ($address !== null) {
      $message->line('Adresse : **'.$address.'**');
    }

    if ($this->order->pickupPoint !== null) {
      $message->line('Point de retrait : **'.$this->order->pickupPoint->name.'** — '.$this->order->pickupPoint->address);
    }

    return $message
      ->action('Ouvrir l\'espace livreur', $livreurUrl)
      ->line('Scannez le QR code du client pour confirmer la livraison.');
  }

  /**
   * Formate l'adresse de livraison en une ligne.
   *
   * @return string|null Adresse formatée
   */
  private function formatAddress(): ?string
  {
    $address = $this->order->shipping_address;

    if (! is_array($address)) {
      return null;
    }

    $parts = array_filter([
      $address['street'] ?? null,
      $address['commune'] ?? null,
      $address['city'] ?? null,
    ]);

    return $parts === [] ? null : implode(', ', $parts);
  }
}
