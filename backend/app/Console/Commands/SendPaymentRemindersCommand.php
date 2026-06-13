<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderNotificationService;
use Illuminate\Console\Command;

/**
 * Envoie un rappel email aux clients dont la commande est en attente de paiement depuis 5 h.
 */
class SendPaymentRemindersCommand extends Command
{
  /**
   * Signature de la commande Artisan.
   *
   * @var string
   */
  protected $signature = 'orders:send-payment-reminders';

  /**
   * Description affichée dans la liste des commandes.
   *
   * @var string
   */
  protected $description = 'Rappel email pour les commandes en attente de paiement (5 h)';

  /**
   * Exécute l'envoi des rappels de paiement.
   *
   * @param OrderNotificationService $notificationService Service de notifications
   */
  public function handle(OrderNotificationService $notificationService): int
  {
    $threshold = now()->subHours(5);

    $orders = Order::query()
      ->where('status', OrderStatus::PendingPayment)
      ->whereNull('payment_reminder_sent_at')
      ->where('created_at', '<=', $threshold)
      ->with('user')
      ->get();

    foreach ($orders as $order) {
      $notificationService->notifyPaymentReminder($order);
      $this->info('Rappel envoyé : '.$order->order_number);
    }

    return self::SUCCESS;
  }
}
