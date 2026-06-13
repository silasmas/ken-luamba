<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payments\InitiatePaymentRequest;
use App\Models\Order;
use App\Services\MobileMoneyOperatorService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur API pour les paiements commande.
 */
class PaymentController extends Controller
{
  /**
   * Initialise le contrôleur paiements.
   *
   * @param PaymentService $paymentService Service d'orchestration paiement
   * @param MobileMoneyOperatorService $operatorService Service opérateurs mobile
   */
  public function __construct(
    private readonly PaymentService $paymentService,
    private readonly MobileMoneyOperatorService $operatorService,
  ) {}

  /**
   * Liste les opérateurs Mobile Money disponibles.
   *
   * @return JsonResponse Opérateurs et règles de numéro
   */
  public function mobileProviders(): JsonResponse
  {
    return response()->json([
      'data' => $this->operatorService->listForApi(),
    ]);
  }

  /**
   * Lance le paiement pour une commande.
   *
   * @param InitiatePaymentRequest $request Données validées
   * @param string $orderNumber Numéro de commande
   * @return JsonResponse Résultat d'initiation
   */
  public function initiate(InitiatePaymentRequest $request, string $orderNumber): JsonResponse
  {
    $order = Order::query()
      ->where('order_number', $orderNumber)
      ->where('user_id', $request->user()->id)
      ->with('payment')
      ->firstOrFail();

    $result = $this->paymentService->initiate(
      $order,
      PaymentChannel::from($request->validated('channel')),
      $request->validated('phone'),
      $request->validated('providerCode'),
    );

    return response()->json([
      'message' => 'Paiement initié.',
      'data' => $result,
    ]);
  }

  /**
   * Vérifie le statut d'un paiement (polling Mobile Money).
   *
   * @param Request $request Requête avec référence
   * @return JsonResponse Statut courant
   */
  public function checkStatus(Request $request): JsonResponse
  {
    $request->validate([
      'reference' => ['required', 'string'],
    ]);

    $result = $this->paymentService->checkAndUpdateStatus(
      $request->string('reference')->toString(),
    );

    return response()->json(['data' => $result]);
  }

  /**
   * Traite le retour après paiement carte.
   *
   * @param Request $request Requête avec référence et statut
   * @return JsonResponse Résultat du retour
   */
  public function cardReturn(Request $request): JsonResponse
  {
    $request->validate([
      'reference' => ['required', 'string'],
      'status' => ['required', 'in:success,cancel,decline'],
    ]);

    $result = $this->paymentService->handleCardReturn(
      $request->string('reference')->toString(),
      $request->string('status')->toString(),
    );

    return response()->json(['data' => $result]);
  }

  /**
   * Webhook callback FlexPay (Mobile Money / carte).
   *
   * @param Request $request Payload FlexPay
   * @return JsonResponse Accusé de réception
   */
  public function flexpayCallback(Request $request): JsonResponse
  {
    $reference = $request->input('reference')
      ?? $request->input('transaction.reference')
      ?? $request->input('orderNumber');

    $status = (int) ($request->input('status')
      ?? $request->input('transaction.status')
      ?? -1);

    if ($reference === null) {
      return response()->json(['message' => 'Référence manquante.'], 422);
    }

    $payment = \App\Models\Payment::query()
      ->where('provider_reference', $reference)
      ->orWhereHas('order', fn ($q) => $q->where('order_number', $reference))
      ->with('order')
      ->first();

    if ($payment === null) {
      return response()->json(['message' => 'Paiement introuvable.'], 404);
    }

    $this->paymentService->applyFlexPayStatus(
      $payment,
      $status,
      (string) ($request->input('message') ?? 'Callback FlexPay'),
    );

    return response()->json(['message' => 'OK']);
  }
}
