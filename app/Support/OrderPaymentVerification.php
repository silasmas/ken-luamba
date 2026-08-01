<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Vérifie auprès de FlexPay le statut d'une commande en attente de paiement.
 */
class OrderPaymentVerification
{
  /**
   * Indique si une commande peut être re-vérifiée auprès de FlexPay.
   *
   * @param Order $order Commande candidate
   * @return bool True si paiement encore en attente / en cours
   */
  public static function canVerify(Order $order): bool
  {
    $order->loadMissing('payment');

    if ($order->status === OrderStatus::PendingPayment) {
      return true;
    }

    $paymentStatus = $order->payment?->status;

    return in_array($paymentStatus, [
      PaymentStatus::Pending,
      PaymentStatus::Processing,
    ], true);
  }

  /**
   * Interroge FlexPay et met à jour la commande / le paiement.
   *
   * @param Order $order Commande à vérifier
   * @return array{success: bool, title: string, message: string, color: string} Résultat UI
   */
  public static function verify(Order $order): array
  {
    try {
      $result = app(PaymentService::class)->checkAndUpdateStatus($order->order_number);
      $order->refresh()->loadMissing('payment');

      $status = $result['status'] ?? null;
      $isPaid = $status === 0 || $order->paid_at !== null;

      if ($isPaid) {
        return [
          'success' => true,
          'title' => 'Paiement confirmé',
          'message' => (string) ($result['message'] ?? 'La transaction a été validée.'),
          'color' => 'success',
        ];
      }

      if ($status === 1) {
        return [
          'success' => false,
          'title' => 'Paiement non abouti',
          'message' => (string) ($result['message'] ?? 'Transaction annulée ou refusée.'),
          'color' => 'danger',
        ];
      }

      return [
        'success' => false,
        'title' => 'Toujours en attente',
        'message' => (string) ($result['message'] ?? 'Aucune confirmation chez FlexPay pour le moment.'),
        'color' => 'warning',
      ];
    } catch (ValidationException $exception) {
      $message = collect($exception->errors())->flatten()->first()
        ?? $exception->getMessage();

      return [
        'success' => false,
        'title' => 'Vérification impossible',
        'message' => (string) $message,
        'color' => 'danger',
      ];
    } catch (Throwable $exception) {
      return [
        'success' => false,
        'title' => 'Erreur FlexPay',
        'message' => $exception->getMessage() ?: 'Impossible de contacter la passerelle.',
        'color' => 'danger',
      ];
    }
  }
}
