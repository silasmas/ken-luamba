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
use App\Services\Mail\MailQuotaService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Service central d'envoi des notifications email liées aux commandes et livraisons.
 */
class OrderNotificationService
{
  /**
   * Notifie le client après un paiement réussi.
   * Les échecs SMTP (quota Hostinger, etc.) n'interrompent jamais le flux paiement.
   *
   * @param Order $order Commande payée
   */
  public function notifyPaymentSuccess(Order $order): void
  {
    try {
      $order = $this->loadOrder($order);

      if ($order->payment_success_email_sent_at === null) {
        $this->sendPaymentSuccessEmail($order);
      }

      if ($order->delivery !== null && $order->admin_pending_delivery_notified_at === null) {
        $notification = new OrderAwaitingDeliveryNotification($order);
        $this->notifyAdmins($notification);

        if ($order->isDirectPayment()) {
          $this->notifyCouriers($notification);
        }

        $order->update(['admin_pending_delivery_notified_at' => now()]);
      }
    } catch (Throwable $exception) {
      Log::error('Échec notifications après paiement (paiement déjà validé).', [
        'order_number' => $order->order_number ?? null,
        'error' => $exception->getMessage(),
      ]);
    }
  }

  /**
   * Renvoie (ou envoie) le mail de confirmation d'achat au client.
   *
   * @param Order $order Commande payée
   * @param int $delaySeconds Délai avant envoi (espacement anti-quota Hostinger)
   * @return array{success: bool, message: string} Résultat pour l'UI admin
   */
  public function resendPaymentSuccessEmail(Order $order, int $delaySeconds = 0): array
  {
    $order = $this->loadOrder($order);

    if ($order->paid_at === null) {
      return [
        'success' => false,
        'message' => 'La commande n\'est pas encore payée.',
      ];
    }

    if ($order->user === null || blank($order->user->email)) {
      return [
        'success' => false,
        'message' => 'Aucun email client associé à cette commande.',
      ];
    }

    $quota = app(MailQuotaService::class);

    if (! $quota->canSend()) {
      $snapshot = $quota->snapshot();

      return [
        'success' => false,
        'message' => sprintf(
          'Quota estimé épuisé (%d/%d sur 24 h). Attendez le reset Hostinger ou augmentez MAIL_DAILY_LIMIT si le plan a changé.',
          $snapshot['used'],
          $snapshot['limit'],
        ),
      ];
    }

    $sent = $this->sendPaymentSuccessEmail($order, $delaySeconds);

    if (! $sent) {
      return [
        'success' => false,
        'message' => 'Échec de mise en file du mail. Vérifiez SMTP, la table `jobs`, et que `queue:work` tourne.',
      ];
    }

    $email = $order->user->email;
    $remaining = $quota->remainingInRolling24h();

    return [
      'success' => true,
      'message' => ($delaySeconds > 0
        ? "Mail mis en file pour {$email} (envoi dans ~{$delaySeconds}s)."
        : "Mail de confirmation mis en file pour {$email}.")
        .' Quota estimé restant : '.$remaining.'/'.$quota->dailyLimit().' (24 h).',
    ];
  }

  /**
   * Envoie le mail de confirmation d'achat et horodate l'envoi.
   *
   * @param Order $order Commande cible
   * @param int $delaySeconds Délai avant dispatch en file
   * @return bool True si la mise en file / envoi a réussi
   */
  private function sendPaymentSuccessEmail(Order $order, int $delaySeconds = 0): bool
  {
    $client = $order->user;

    if ($client === null || blank($client->email)) {
      return false;
    }

    try {
      $notification = new OrderPaymentSuccessNotification($order);

      if ($delaySeconds > 0) {
        $notification->delay(now()->addSeconds($delaySeconds));
      }

      $client->notify($notification);
      $order->forceFill(['payment_success_email_sent_at' => now()])->save();

      return true;
    } catch (Throwable $exception) {
      Log::error('Échec envoi mail confirmation achat.', [
        'order_number' => $order->order_number,
        'user_id' => $client->id,
        'error' => $exception->getMessage(),
      ]);

      return false;
    }
  }

  /**
   * Notifie le client après un échec de paiement.
   * Ne lève jamais d'exception (SMTP Hostinger, etc.).
   *
   * @param Order $order Commande concernée
   * @param string $reason Motif affiché au client
   */
  public function notifyPaymentFailed(Order $order, string $reason): void
  {
    try {
      $order = $this->loadOrder($order);
      $client = $order->user;

      if ($client !== null && filled($client->email)) {
        $client->notify(new OrderPaymentFailedNotification($order, $reason));
      }
    } catch (Throwable $exception) {
      Log::error('Échec notification échec paiement (statut déjà mis à jour).', [
        'order_number' => $order->order_number ?? null,
        'error' => $exception->getMessage(),
      ]);
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
      ->each(function (User $admin) use ($notification): void {
        try {
          $admin->notify(clone $notification);
        } catch (Throwable $exception) {
          Log::warning('Échec notification admin.', [
            'admin_id' => $admin->id,
            'error' => $exception->getMessage(),
          ]);
        }
      });
  }

  /**
   * Envoie une notification à tous les livreurs actifs.
   *
   * @param Notification $notification Notification à diffuser
   */
  private function notifyCouriers(Notification $notification): void
  {
    User::query()
      ->where('role', UserRole::Courier)
      ->where('is_active', true)
      ->get()
      ->each(function (User $courier) use ($notification): void {
        try {
          $courier->notify(clone $notification);
        } catch (Throwable $exception) {
          Log::warning('Échec notification livreur.', [
            'courier_id' => $courier->id,
            'error' => $exception->getMessage(),
          ]);
        }
      });
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
