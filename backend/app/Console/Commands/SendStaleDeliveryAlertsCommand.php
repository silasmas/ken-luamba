<?php

namespace App\Console\Commands;

use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Services\OrderNotificationService;
use Illuminate\Console\Command;

/**
 * Alerte les administrateurs pour les livraisons assignées sans évolution depuis 4 h.
 */
class SendStaleDeliveryAlertsCommand extends Command
{
  /**
   * Signature de la commande Artisan.
   *
   * @var string
   */
  protected $signature = 'deliveries:send-stale-alerts';

  /**
   * Description affichée dans la liste des commandes.
   *
   * @var string
   */
  protected $description = 'Alerte admin pour livraisons assignées bloquées (4 h)';

  /**
   * Exécute l'envoi des alertes livraison bloquée.
   *
   * @param OrderNotificationService $notificationService Service de notifications
   */
  public function handle(OrderNotificationService $notificationService): int
  {
    $threshold = now()->subHours(4);

    $deliveries = Delivery::query()
      ->where('status', DeliveryStatus::Assigned)
      ->whereNotNull('assigned_at')
      ->where('assigned_at', '<=', $threshold)
      ->whereNull('stale_assignment_notified_at')
      ->with(['order.user', 'courier'])
      ->get();

    foreach ($deliveries as $delivery) {
      $notificationService->notifyStaleDeliveryAssignment($delivery);
      $this->info('Alerte envoyée : '.($delivery->order?->order_number ?? $delivery->id));
    }

    return self::SUCCESS;
  }
}
