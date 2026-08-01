<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Middleware\RateLimited;

/**
 * Email envoyé au client après un paiement réussi (file d'attente + rate limit).
 */
class OrderPaymentSuccessNotification extends Notification implements ShouldQueue
{
  use Queueable;

  /**
   * Nombre de tentatives en cas d'échec SMTP temporaire.
   */
  public int $tries = 3;

  /**
   * Initialise la notification de paiement réussi.
   *
   * @param Order $order Commande payée
   */
  public function __construct(private readonly Order $order) {}

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
   * Construit l'email de confirmation de paiement.
   *
   * @param mixed $notifiable Destinataire
   * @return MailMessage Message email
   */
  public function toMail(mixed $notifiable): MailMessage
  {
    $frontendUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');
    $isDirect = $this->order->isDirectPayment() && filled($this->order->public_token);
    $orderUrl = $isDirect
      ? $frontendUrl.'/paiement-direct/result/'.$this->order->public_token
      : $frontendUrl.'/espace/commandes/'.$this->order->order_number;

    $mail = (new MailMessage)
      ->subject('Paiement confirmé — '.$this->order->order_number)
      ->greeting('Bonjour '.($notifiable->full_name ?? '').',')
      ->line('Votre paiement pour la commande **'.$this->order->order_number.'** a été confirmé.')
      ->line('Montant : **'.number_format((float) $this->order->total, 0, ',', ' ').' '.$this->order->currency.'**');

    if ($isDirect) {
      $mail
        ->line('Présentez le QR code de remise affiché sur la page de confirmation au livreur.')
        ->action('Voir ma confirmation et mon QR', $orderUrl);
    } else {
      $mail->action('Voir ma commande', $orderUrl);
    }

    return $mail->line('Merci pour votre confiance !');
  }
}
