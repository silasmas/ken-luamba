<?php

namespace App\Notifications\Orders;

use App\Models\Delivery;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerte admin : livraison assignée sans évolution depuis 4 h.
 */
class DeliveryStaleAssignmentNotification extends Notification
{
  use Queueable;

  /**
   * Initialise l'alerte de livraison bloquée.
   *
   * @param Delivery $delivery Livraison concernée
   */
  public function __construct(private readonly Delivery $delivery) {}

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
   * Construit l'email d'alerte livraison bloquée.
   *
   * @param mixed $notifiable Destinataire admin
   * @return MailMessage Message email
   */
  public function toMail(mixed $notifiable): MailMessage
  {
    $order = $this->delivery->order;
    $courierName = $this->delivery->courier?->full_name ?? '—';
    $clientName = $order?->user?->full_name ?? '—';
    $assignedAt = $this->delivery->assigned_at?->format('d/m/Y H:i') ?? '—';

    return (new MailMessage)
      ->subject('Alerte livraison bloquée — '.($order?->order_number ?? '—'))
      ->greeting('Bonjour,')
      ->line('Une livraison assignée n\'a pas évolué depuis plus de 4 heures.')
      ->line('Commande : **'.($order?->order_number ?? '—').'**')
      ->line('Client : **'.$clientName.'** — Livreur : **'.$courierName.'**')
      ->line('Assignée le : **'.$assignedAt.'**')
      ->line('Statut actuel : **'.$this->delivery->status->label().'**');
  }
}
