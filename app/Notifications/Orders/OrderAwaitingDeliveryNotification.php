<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Middleware\RateLimited;

/**
 * Alerte admin / livreur : commande payée en attente de livraison.
 */
class OrderAwaitingDeliveryNotification extends Notification implements ShouldQueue
{
  use Queueable;

  /**
   * Nombre de tentatives en cas d'échec SMTP temporaire.
   */
  public int $tries = 3;

  /**
   * Initialise l'alerte commande en attente de livraison.
   *
   * @param Order $order Commande payée avec livraison physique
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
   * Construit l'email d'alerte admin ou livreur.
   *
   * @param mixed $notifiable Destinataire
   * @return MailMessage Message email
   */
  public function toMail(mixed $notifiable): MailMessage
  {
    $clientEmail = $this->order->user?->email ?? '—';
    $isDirect = $this->order->isDirectPayment();

    $mail = (new MailMessage)
      ->subject('Commande à remettre — '.$this->order->order_number)
      ->greeting('Bonjour,')
      ->line('Une commande payée attend une remise / livraison.')
      ->line('Commande : **'.$this->order->order_number.'**')
      ->line('Montant : **'.number_format((float) $this->order->total, 0, ',', ' ').' '.$this->order->currency.'**');

    if ($isDirect) {
      $mail
        ->line('Canal : **vente directe**')
        ->line('Email client : **'.$clientEmail.'**')
        ->line('Le client présentera son QR code de remise.');
    } else {
      $mail
        ->line('Canal : **boutique**')
        ->line('Client : **'.($this->order->user?->full_name ?? '—').'**');
    }

    return $mail->action('Voir la commande', url('/admin/orders/'.$this->order->id.'/edit'));
  }
}
