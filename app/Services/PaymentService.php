<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\FlexPay\FlexPayCardService;
use App\Services\FlexPay\FlexPayMobileService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Service d'orchestration des paiements FlexPay pour les commandes.
 */
class PaymentService
{
  /**
   * Initialise le service paiement.
   *
   * @param FlexPayMobileService $mobileService Service FlexPay mobile
   * @param FlexPayCardService $cardService Service FlexPay carte
   * @param QrCodeService $qrCodeService Service QR code
   * @param DeliveryService $deliveryService Service livraison
   * @param DigitalAccessService $digitalAccessService Service accès numériques
   */
  public function __construct(
    private readonly FlexPayMobileService $mobileService,
    private readonly FlexPayCardService $cardService,
    private readonly QrCodeService $qrCodeService,
    private readonly DeliveryService $deliveryService,
    private readonly DigitalAccessService $digitalAccessService,
    private readonly MobileMoneyOperatorService $operatorService,
    private readonly CartService $cartService,
    private readonly OrderNotificationService $notificationService,
  ) {}

  /**
   * Lance le paiement FlexPay pour une commande.
   *
   * @param Order $order Commande en attente
   * @param PaymentChannel $channel Canal de paiement
   * @param string|null $phone Téléphone Mobile Money
   * @param string|null $providerCode Code opérateur mobile
   * @return array<string, mixed> Résultat initiation
   */
  public function initiate(
    Order $order,
    PaymentChannel $channel,
    ?string $phone = null,
    ?string $providerCode = null,
  ): array {
    if ($order->status !== OrderStatus::PendingPayment) {
      throw ValidationException::withMessages([
        'order' => ['Cette commande ne peut plus être payée.'],
      ]);
    }

    $payment = $order->payment;

    if ($payment === null) {
      throw ValidationException::withMessages([
        'order' => ['Paiement introuvable pour cette commande.'],
      ]);
    }

    if ($payment->status === PaymentStatus::Completed) {
      throw ValidationException::withMessages([
        'order' => ['Cette commande est déjà payée.'],
      ]);
    }

    if (! in_array($payment->status, [
      PaymentStatus::Pending,
      PaymentStatus::Failed,
      PaymentStatus::Cancelled,
      PaymentStatus::Processing,
    ], true)) {
      throw ValidationException::withMessages([
        'order' => ['Paiement non disponible pour cette commande.'],
      ]);
    }

    if ($channel === PaymentChannel::MobileMoney) {
      if ($order->currency !== 'CDF') {
        throw ValidationException::withMessages([
          'payment' => ['Le Mobile Money est disponible uniquement pour les commandes en CDF. Utilisez la carte bancaire pour les commandes en USD.'],
        ]);
      }

      if ($phone === null || $providerCode === null) {
        throw ValidationException::withMessages([
          'phone' => ['Sélectionnez un opérateur et saisissez votre numéro Mobile Money.'],
        ]);
      }

      $provider = $this->operatorService->validate($providerCode, $phone);
      $operatorLabel = (string) ($provider['label'] ?? 'votre opérateur');

      $result = $this->mobileService->initiate(
        $order->order_number,
        (float) $order->total,
        $order->currency,
        $phone,
      );

      if (! $result['success']) {
        $payment->update(['status' => PaymentStatus::Failed, 'metadata' => $result]);
        $order = $payment->order ?? $order;
        $this->notificationService->notifyPaymentFailed(
          $order->loadMissing('user'),
          $result['message'] ?? 'Le paiement Mobile Money a échoué.',
        );

        throw ValidationException::withMessages([
          'payment' => [$result['message']],
        ]);
      }

      $payment->update([
        'channel' => PaymentChannel::MobileMoney,
        'phone' => $phone,
        'status' => PaymentStatus::Processing,
        'provider_reference' => $result['orderNumber'] ?? null,
        'metadata' => array_merge($result, [
          'providerCode' => $providerCode,
          'providerLabel' => $operatorLabel,
        ]),
      ]);

      return [
        'type' => 'mobile_money',
        'message' => 'Une demande de paiement a été envoyée à '.$operatorLabel.'. Validez sur votre téléphone '.$phone.'.',
        'operatorLabel' => $operatorLabel,
        'providerCode' => $providerCode,
        'phone' => $phone,
        'orderNumber' => $result['orderNumber'] ?? null,
        'reference' => $order->order_number,
        'steps' => [
          ['id' => 'order', 'label' => 'Commande enregistrée', 'status' => 'done'],
          ['id' => 'request', 'label' => 'Demande envoyée à '.$operatorLabel, 'status' => 'done'],
          ['id' => 'confirm', 'label' => 'Confirmez le paiement sur votre téléphone', 'status' => 'active'],
          ['id' => 'verify', 'label' => 'Vérification du paiement', 'status' => 'pending'],
        ],
      ];
    }

    $result = $this->cardService->initiate(
      $order->order_number,
      (float) $order->total,
      $order->currency,
      'Commande Ken Luamba — '.$order->order_number,
    );

    if (! $result['success']) {
      $payment->update(['status' => PaymentStatus::Failed, 'metadata' => $result]);
      $this->notificationService->notifyPaymentFailed(
        $order->loadMissing('user'),
        $result['message'] ?? 'Le paiement par carte a échoué.',
      );

      throw ValidationException::withMessages([
        'payment' => [$result['message'] ?? 'Échec paiement carte.'],
      ]);
    }

    $payment->update([
      'channel' => PaymentChannel::Card,
      'status' => PaymentStatus::Processing,
      'provider_reference' => $result['orderNumber'] ?? null,
      'metadata' => $result,
    ]);

    return [
      'type' => 'card',
      'redirectUrl' => $result['url'],
      'orderNumber' => $result['orderNumber'] ?? null,
      'reference' => $order->order_number,
    ];
  }

  /**
   * Vérifie et met à jour le statut d'un paiement FlexPay.
   *
   * @param string $orderNumber Référence FlexPay ou numéro commande
   * @return array<string, mixed> Statut courant
   */
  public function checkAndUpdateStatus(string $orderNumber): array
  {
    $payment = Payment::query()
      ->where('provider_reference', $orderNumber)
      ->orWhereHas('order', fn ($q) => $q->where('order_number', $orderNumber))
      ->with('order')
      ->first();

    if ($payment === null) {
      throw ValidationException::withMessages([
        'reference' => ['Paiement introuvable.'],
      ]);
    }

    $checkRef = $payment->provider_reference ?? $orderNumber;
    $result = $this->mobileService->checkStatus($checkRef);

    return $this->applyFlexPayStatus($payment, $result['status'], $result['message'], true);
  }

  /**
   * Marque une commande comme payée après retour carte ou callback.
   *
   * @param string $orderNumber Numéro de commande
   * @param string $status success|cancel|decline
   * @return array<string, mixed> Résultat
   */
  public function handleCardReturn(string $orderNumber, string $status): array
  {
    $payment = Payment::query()
      ->whereHas('order', fn ($q) => $q->where('order_number', $orderNumber))
      ->with('order')
      ->firstOrFail();

    if ($status === 'success') {
      return $this->markAsPaid($payment, 'Paiement carte confirmé');
    }

    $payment->update(['status' => PaymentStatus::Cancelled]);
    $payment->order?->update(['status' => OrderStatus::PendingPayment]);

    if ($payment->order !== null) {
      $message = $status === 'decline'
        ? 'Paiement refusé. Vous pouvez réessayer.'
        : 'Paiement annulé. Vous pouvez réessayer.';
      $this->notificationService->notifyPaymentFailed(
        $payment->order->loadMissing('user'),
        $message,
      );
    }

    return [
      'success' => false,
      'message' => $status === 'decline' ? 'Paiement refusé. Vous pouvez réessayer.' : 'Paiement annulé. Vous pouvez réessayer.',
      'orderNumber' => $payment->order?->order_number,
    ];
  }

  /**
   * Applique le statut FlexPay (0=payé, 1=annulé, 2=attente).
   *
   * @param Payment $payment Paiement cible
   * @param int $status Code statut passerelle (0=payé, 1=annulé, 2=attente)
   * @param string $message Message technique
   * @param bool $forPolling Réponse orientée client pour le polling
   * @return array<string, mixed> Résultat
   */
  public function applyFlexPayStatus(
    Payment $payment,
    int $status,
    string $message,
    bool $forPolling = false,
  ): array {
    $operatorLabel = (string) ($payment->metadata['providerLabel'] ?? 'votre opérateur');

    return match ($status) {
      0 => $this->markAsPaid($payment, 'Paiement confirmé. Merci pour votre commande !'),
      1 => tap([
        'success' => false,
        'status' => $status,
        'message' => 'Paiement annulé ou refusé sur '.$operatorLabel.'. Vous pouvez réessayer.',
        'orderNumber' => $payment->order?->order_number,
        'steps' => $forPolling ? $this->buildPollingSteps($operatorLabel, 'error') : null,
      ], function () use ($payment, $operatorLabel): void {
        $payment->update(['status' => PaymentStatus::Failed]);

        if ($payment->order !== null) {
          $this->notificationService->notifyPaymentFailed(
            $payment->order->loadMissing('user'),
            'Paiement annulé ou refusé sur '.$operatorLabel.'.',
          );
        }
      }),
      2 => [
        'success' => true,
        'status' => $status,
        'message' => 'En attente de validation sur '.$operatorLabel.'. Consultez votre téléphone.',
        'orderNumber' => $payment->provider_reference,
        'steps' => $forPolling ? $this->buildPollingSteps($operatorLabel, 'waiting') : null,
      ],
      default => [
        'success' => false,
        'status' => $status,
        'message' => 'Paiement en cours de traitement. Patientez quelques instants.',
        'steps' => $forPolling ? $this->buildPollingSteps($operatorLabel, 'waiting') : null,
      ],
    };
  }

  /**
   * Construit les étapes affichées pendant le polling Mobile Money.
   *
   * @param string $operatorLabel Nom de l'opérateur
   * @param string $phase Phase courante (waiting|error)
   * @return array<int, array<string, string>> Étapes UI
   */
  private function buildPollingSteps(string $operatorLabel, string $phase): array
  {
    $verifyStatus = $phase === 'error' ? 'error' : 'active';
    $confirmStatus = $phase === 'error' ? 'error' : 'done';

    return [
      ['id' => 'order', 'label' => 'Commande enregistrée', 'status' => 'done'],
      ['id' => 'request', 'label' => 'Demande envoyée à '.$operatorLabel, 'status' => 'done'],
      ['id' => 'confirm', 'label' => 'Confirmation sur votre téléphone', 'status' => $confirmStatus],
      ['id' => 'verify', 'label' => 'Vérification du paiement', 'status' => $verifyStatus],
    ];
  }

  /**
   * Marque le paiement et la commande comme payés.
   *
   * @param Payment $payment Paiement confirmé
   * @param string $message Message de succès
   * @return array<string, mixed> Résultat
   */
  private function markAsPaid(Payment $payment, string $message): array
  {
    return DB::transaction(function () use ($payment, $message): array {
      $payment->update([
        'status' => PaymentStatus::Completed,
        'paid_at' => now(),
      ]);

      $order = $payment->order;
      $order?->loadMissing('items');

      $order?->update([
        'status' => OrderStatus::Paid,
        'paid_at' => now(),
      ]);

      if ($order !== null) {
        if ($order->hasPhysicalItems()) {
          $this->qrCodeService->generateForOrder($order);
          $this->deliveryService->createForOrder($order);
        } else {
          $order->update([
            'status' => OrderStatus::Completed,
            'completed_at' => now(),
          ]);
        }

        $this->digitalAccessService->grantForOrder($order);
        $this->cartService->clearUserCart($order->user_id);
        $order->refresh()->load(['qrCode', 'delivery', 'user', 'items']);

        $this->notificationService->afterCommit(function () use ($order): void {
          $this->notificationService->notifyPaymentSuccess($order);
        });
      }

      return [
        'success' => true,
        'status' => 0,
        'message' => $message,
        'orderId' => $order?->id,
        'orderNumber' => $order?->order_number,
        'hasPhysicalItems' => $order?->hasPhysicalItems() ?? false,
        'isDigitalOnly' => $order?->isDigitalOnly() ?? false,
        'qrToken' => $order?->hasPhysicalItems() ? $order?->qrCode?->token : null,
        'steps' => [
          ['id' => 'order', 'label' => 'Commande enregistrée', 'status' => 'done'],
          ['id' => 'request', 'label' => 'Demande envoyée à votre opérateur', 'status' => 'done'],
          ['id' => 'confirm', 'label' => 'Confirmation sur votre téléphone', 'status' => 'done'],
          ['id' => 'verify', 'label' => 'Paiement confirmé', 'status' => 'done'],
        ],
      ];
    });
  }
}
