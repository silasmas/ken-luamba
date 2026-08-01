<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Middleware\RateLimited;

/**
 * Email envoyé au client après un échec de paiement.
 */
class OrderPaymentFailedNotification extends Notification implements ShouldQueue
{
  use Queueable;

  /**
   * Nombre de tentatives en cas d'échec SMTP temporaire.
   */
  public int $tries = 3;

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
   * Middleware de file : limite le débit SMTP Hostinger.
   *
   * @return array<int, object>
   */
  public function middleware(): array
  {
    return [new RateLimited('hostinger-mail')];
  }

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
    $isDirect = $this->order->isDirectPayment() && filled($this->order->public_token);
    $orderUrl = $isDirect
      ? $frontendUrl.'/paiement-direct'
      : $frontendUrl.'/espace/commandes/'.$this->order->order_number;

    return (new MailMessage)
      ->subject('Paiement non abouti — '.$this->order->order_number)
      ->greeting('Bonjour '.($notifiable->full_name ?? '').',')
      ->line('Le paiement de votre commande **'.$this->order->order_number.'** n\'a pas abouti.')
      ->line($this->reason)
      ->action($isDirect ? 'Réessayer le paiement' : 'Reprendre le paiement', $orderUrl)
      ->line('Vous pouvez réessayer quand vous le souhaitez.');
  }
}
