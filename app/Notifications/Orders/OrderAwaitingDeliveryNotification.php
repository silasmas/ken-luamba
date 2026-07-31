<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerte admin / livreur : commande payée en attente de livraison.
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
   * Construit l'email d'alerte admin ou livreur.
   *
   * @param mixed $notifiable Destinataire
   * @return MailMessage Message email
   */
  public function toMail(mixed $notifiable): MailMessage
  {
    $clientName = $this->order->user?->full_name ?? '—';
    $clientEmail = $this->order->user?->email ?? '—';
    $isDirect = $this->order->isDirectPayment();

    $mail = (new MailMessage)
      ->subject(($isDirect ? 'Achat direct à remettre' : 'Commande à livrer').' — '.$this->order->order_number)
      ->greeting('Bonjour,');

    if ($isDirect) {
      $mail
        ->line('Un client a effectué un **achat direct** qu\'il faut vérifier et lui remettre.')
        ->line('Email client : **'.$clientEmail.'**')
        ->line('Nom : **'.$clientName.'**')
        ->line('Commande : **'.$this->order->order_number.'**')
        ->line('Montant : **'.number_format((float) $this->order->total, 0, ',', ' ').' '.$this->order->currency.'**')
        ->line('Scannez le QR code du client pour finaliser la remise.');
    } else {
      $mail
        ->line('Une commande payée attend une livraison.')
        ->line('Commande : **'.$this->order->order_number.'**')
        ->line('Client : **'.$clientName.'**')
        ->line('Montant : **'.number_format((float) $this->order->total, 0, ',', ' ').' '.$this->order->currency.'**')
        ->line('Assignez un livreur depuis l\'administration.');
    }

    return $mail;
  }
}
