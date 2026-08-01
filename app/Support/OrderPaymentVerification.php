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

      return self::buildResultFromGateway($order, $result);
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
      $order->refresh()->loadMissing('payment');

      // Le paiement peut déjà être validé alors qu'un mail SMTP a échoué ensuite.
      if ($order->paid_at !== null) {
        return [
          'success' => true,
          'title' => 'Paiement confirmé',
          'message' => 'Transaction validée. '
            .self::humanizeSecondaryError($exception->getMessage()),
          'color' => 'success',
        ];
      }

      return self::buildFailureFromException($exception);
    }
  }

  /**
   * Construit le résultat UI à partir de la réponse FlexPay.
   *
   * @param Order $order Commande rafraîchie
   * @param array<string, mixed> $result Réponse PaymentService
   * @return array{success: bool, title: string, message: string, color: string}
   */
  private static function buildResultFromGateway(Order $order, array $result): array
  {
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
  }

  /**
   * Transforme une exception en message admin compréhensible.
   *
   * @param Throwable $exception Exception capturée
   * @return array{success: bool, title: string, message: string, color: string}
   */
  private static function buildFailureFromException(Throwable $exception): array
  {
    $raw = $exception->getMessage();

    if (self::isHostingerMailRateLimit($raw)) {
      return [
        'success' => false,
        'title' => 'Quota email Hostinger dépassé',
        'message' => 'Ce n\'est pas une erreur FlexPay. Hostinger bloque les mails (limite d\'envoi). '
          .'Attendez le reset du quota, puis renvoyez le mail d\'achat séparément si besoin.',
        'color' => 'warning',
      ];
    }

    return [
      'success' => false,
      'title' => 'Erreur de vérification',
      'message' => $raw ?: 'Impossible de contacter la passerelle.',
      'color' => 'danger',
    ];
  }

  /**
   * Message secondaire quand le paiement est OK mais un traitement annexe a échoué.
   *
   * @param string $raw Message technique
   * @return string Message utilisateur
   */
  private static function humanizeSecondaryError(string $raw): string
  {
    if (self::isHostingerMailRateLimit($raw)) {
      return 'Le mail n\'a pas pu partir (quota Hostinger). Utilisez « Renvoyer mail achat » plus tard.';
    }

    return 'Un traitement annexe a échoué : '.$raw;
  }

  /**
   * Détecte le rate-limit SMTP Hostinger dans un message d'exception.
   *
   * @param string $message Message brut
   * @return bool True si quota mail Hostinger
   */
  private static function isHostingerMailRateLimit(string $message): bool
  {
    $haystack = strtolower($message);

    return str_contains($haystack, 'hostinger_out_ratelimit')
      || str_contains($haystack, 'ratelimit')
      || (str_contains($haystack, '451') && str_contains($haystack, '4.7.1'));
  }
}
