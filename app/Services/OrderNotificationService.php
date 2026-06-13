<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\User;
use App\Notifications\Orders\DeliveryAssignedClientNotification;
use App\Notifications\Orders\DeliveryAssignedCourierNotification;
use App\Notifications\Orders\DeliveryConfirmedByClientNotification;
use App\Notifications\Orders\DeliveryConfirmedByCourierNotification;
use App\Notifications\Orders\DeliveryDisputedNotification;
use App\Notifications\Orders\DeliveryStaleAssignmentNotification;
use App\Notifications\Orders\OrderAwaitingDeliveryNotification;
use App\Notifications\Orders\OrderPaymentFailedNotification;
use App\Notifications\Orders\OrderPaymentReminderNotification;
use App\Notifications\Orders\OrderPaymentSuccessNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;

/**
 * Service central d'envoi des notifications email liées aux commandes et livraisons.
 */
class OrderNotificationService
{
  /**
   * Notifie le client après un paiement réussi.
   *
   * @param Order $order Commande payée
   */
  public function notifyPaymentSuccess(Order $order): void
  {
    $order = $this->loadOrder($order);
    $client = $order->user;

    if ($client !== null) {
      $client->notify(new OrderPaymentSuccessNotification($order));
    }

    if ($order->delivery !== null && $order->admin_pending_delivery_notified_at === null) {
      $this->notifyAdmins(new OrderAwaitingDeliveryNotification($order));
      $order->update(['admin_pending_delivery_notified_at' => now()]);
    }
  }

  /**
   * Notifie le client après un échec de paiement.
   *
   * @param Order $order Commande concernée
   * @param string $reason Motif affiché au client
   */
  public function notifyPaymentFailed(Order $order, string $reason): void
  {
    $order = $this->loadOrder($order);
    $client = $order->user;

    if ($client !== null) {
      $client->notify(new OrderPaymentFailedNotification($order, $reason));
    }
  }

  /**
   * Notifie client et livreur lors de l'assignation d'une livraison.
   *
   * @param Delivery $delivery Livraison assignée
   */
  public function notifyDeliveryAssigned(Delivery $delivery): void
  {
    $delivery = $this->loadDelivery($delivery);
    $order = $delivery->order;
    $courier = $delivery->courier;
    $client = $order?->user;

    if ($client !== null) {
      $client->notify(new DeliveryAssignedClientNotification($order, $courier));
    }

    if ($courier !== null) {
      $courier->notify(new DeliveryAssignedCourierNotification($order, $courier));
    }
  }

  /**
   * Notifie toutes les parties après confirmation livreur (scan QR).
   *
   * @param Order $order Commande livrée par le livreur
   */
  public function notifyDeliveryConfirmedByCourier(Order $order): void
  {
    $order = $this->loadOrder($order);
    $courier = $order->delivery?->courier;
    $client = $order->user;

    if ($client !== null) {
      $client->notify(new DeliveryConfirmedByCourierNotification($order, $courier));
    }

    if ($courier !== null) {
      $courier->notify(new DeliveryConfirmedByCourierNotification($order, $courier));
    }

    $this->notifyAdmins(new DeliveryConfirmedByCourierNotification($order, $courier));
  }

  /**
   * Notifie toutes les parties après confirmation client.
   *
   * @param Order $order Commande terminée
   */
  public function notifyDeliveryConfirmedByClient(Order $order): void
  {
    $order = $this->loadOrder($order);
    $courier = $order->delivery?->courier;
    $client = $order->user;

    if ($client !== null) {
      $client->notify(new DeliveryConfirmedByClientNotification($order, $courier));
    }

    if ($courier !== null) {
      $courier->notify(new DeliveryConfirmedByClientNotification($order, $courier));
    }

    $this->notifyAdmins(new DeliveryConfirmedByClientNotification($order, $courier));
  }

  /**
   * Notifie le livreur et les admins quand le client conteste une livraison.
   *
   * @param Order $order Commande en litige
   * @param string|null $reason Motif du client
   */
  public function notifyDeliveryDisputed(Order $order, ?string $reason = null): void
  {
    $order = $this->loadOrder($order);
    $courier = $order->delivery?->courier;
    $notification = new DeliveryDisputedNotification($order, $courier, $reason);

    if ($courier !== null) {
      $courier->notify($notification);
    }

    $this->notifyAdmins($notification);
  }

  /**
   *
   * @param Order $order Commande en attente
   */
  public function notifyPaymentReminder(Order $order): void
  {
    $order = $this->loadOrder($order);
    $client = $order->user;

    if ($client !== null) {
      $client->notify(new OrderPaymentReminderNotification($order));
    }

    $order->update(['payment_reminder_sent_at' => now()]);
  }

  /**
   * Alerte admin : livraison assignée sans évolution depuis 4 h.
   *
   * @param Delivery $delivery Livraison bloquée
   */
  public function notifyStaleDeliveryAssignment(Delivery $delivery): void
  {
    $delivery = $this->loadDelivery($delivery);
    $this->notifyAdmins(new DeliveryStaleAssignmentNotification($delivery));
    $delivery->update(['stale_assignment_notified_at' => now()]);
  }

  /**
   * Envoie une notification à tous les administrateurs actifs.
   *
   * @param Notification $notification Notification à diffuser
   */
  private function notifyAdmins(Notification $notification): void
  {
    User::query()
      ->where('role', UserRole::Admin)
      ->where('is_active', true)
      ->get()
      ->each(fn (User $admin) => $admin->notify($notification));
  }

  /**
   * Charge les relations utiles pour une commande.
   *
   * @param Order $order Commande source
   * @return Order Commande enrichie
   */
  private function loadOrder(Order $order): Order
  {
    return $order->loadMissing(['user', 'items', 'delivery.courier', 'pickupPoint']);
  }

  /**
   * Charge les relations utiles pour une livraison.
   *
   * @param Delivery $delivery Livraison source
   * @return Delivery Livraison enrichie
   */
  private function loadDelivery(Delivery $delivery): Delivery
  {
    return $delivery->loadMissing(['order.user', 'order.items', 'order.pickupPoint', 'courier']);
  }

  /**
   * Planifie une notification après validation de la transaction courante.
   *
   * @param callable $callback Action de notification
   */
  public function afterCommit(callable $callback): void
  {
    if (DB::transactionLevel() > 0) {
      DB::afterCommit($callback);

      return;
    }

    $callback();
  }
}
