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
 * La mise à jour du statut est indépendante de l'envoi des mails.
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
   * Interroge FlexPay, met à jour les statuts, puis signale l'état du mail.
   *
   * @param Order $order Commande à vérifier
   * @return array{success: bool, title: string, message: string, color: string} Résultat UI
   */
  public static function verify(Order $order): array
  {
    $gatewayError = null;

    try {
      $order->loadMissing('payment');

      if ($order->payment === null) {
        throw ValidationException::withMessages([
          'payment' => ['Aucun paiement lié à cette commande.'],
        ]);
      }

      app(PaymentService::class)->checkAndUpdatePayment($order->payment);
    } catch (ValidationException $exception) {
      $gatewayError = collect($exception->errors())->flatten()->first()
        ?? $exception->getMessage();
    } catch (Throwable $exception) {
      $gatewayError = $exception->getMessage() ?: 'Erreur technique pendant la vérification.';
    }

    $order->refresh()->loadMissing(['payment', 'user']);

    return self::buildResultFromOrderState($order, $gatewayError);
  }

  /**
   * Construit le message admin à partir de l'état BDD après vérification.
   *
   * @param Order $order Commande rafraîchie
   * @param string|null $gatewayError Erreur technique éventuelle
   * @return array{success: bool, title: string, message: string, color: string}
   */
  private static function buildResultFromOrderState(Order $order, ?string $gatewayError): array
  {
    $payment = $order->payment;
    $mailNote = self::mailStatusNote($order);

    if ($order->paid_at !== null || $payment?->status === PaymentStatus::Completed) {
      return [
        'success' => true,
        'title' => 'Paiement confirmé',
        'message' => 'Transaction mise à jour : payée. '.$mailNote,
        'color' => 'success',
      ];
    }

    if (
      $payment?->status === PaymentStatus::Failed
      || $payment?->status === PaymentStatus::Cancelled
    ) {
      $reason = is_array($payment?->metadata)
        ? (string) ($payment->metadata['failureReason'] ?? '')
        : '';

      return [
        'success' => false,
        'title' => 'Paiement annulé / refusé',
        'message' => trim(
          'Transaction mise à jour : '
          .($payment->status->label())
          .' · Commande toujours '
          .$order->status->label()
          .' (nouvel essai possible)'
          .($reason !== '' ? ' — '.$reason : '')
          .'. '.$mailNote
        ),
        'color' => 'danger',
      ];
    }

    if ($gatewayError !== null) {
      if (self::isHostingerMailRateLimit($gatewayError)) {
        return [
          'success' => false,
          'title' => 'Toujours en attente',
          'message' => 'Le statut FlexPay n\'a pas changé (toujours en attente). '
            .'Note mail : quota Hostinger dépassé — cela n\'empêche pas la vérification du paiement.',
          'color' => 'warning',
        ];
      }

      return [
        'success' => false,
        'title' => 'Vérification incomplète',
        'message' => $gatewayError,
        'color' => 'danger',
      ];
    }

    return [
      'success' => false,
      'title' => 'Toujours en attente',
      'message' => 'Aucune confirmation chez FlexPay pour le moment. '.$mailNote,
      'color' => 'warning',
    ];
  }

  /**
   * Indique clairement si le mail d'achat est parti ou non.
   *
   * @param Order $order Commande
   * @return string Note mail
   */
  private static function mailStatusNote(Order $order): string
  {
    if ($order->paid_at === null) {
      return $order->user?->email
        ? 'Mail d\'achat : non applicable (commande non payée).'
        : 'Mail d\'achat : non applicable.';
    }

    if ($order->payment_success_email_sent_at !== null) {
      return 'Mail d\'achat : envoyé (ou mis en file) le '
        .OrderAdminFormatter::formatLocalizedDateTime($order->payment_success_email_sent_at).'.';
    }

    return 'Mail d\'achat : non parti — utilisez « Renvoyer mail achat » quand le quota Hostinger le permet.';
  }

  /**
   * Détecte le rate-limit SMTP Hostinger.
   *
   * @param string $message Message brut
   * @return bool True si quota mail
   */
  private static function isHostingerMailRateLimit(string $message): bool
  {
    $haystack = strtolower($message);

    return str_contains($haystack, 'hostinger_out_ratelimit')
      || str_contains($haystack, 'ratelimit')
      || (str_contains($haystack, '451') && str_contains($haystack, '4.7.1'));
  }
}
