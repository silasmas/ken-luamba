<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Services\DeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur API pour l'espace livreur (courses, scan QR, confirmations).
 */
class CourierController extends Controller
{
  /**
   * Initialise le contrôleur livreur.
   *
   * @param DeliveryService $deliveryService Service livraison
   */
  public function __construct(
    private readonly DeliveryService $deliveryService,
  ) {}

  /**
   * Liste les courses du livreur et les livraisons disponibles.
   *
   * @param Request $request Requête authentifiée
   * @return JsonResponse Livraisons assignées et disponibles
   */
  public function deliveries(Request $request): JsonResponse
  {
    $courier = $request->user();
    $mine = $this->deliveryService->listForCourier($courier);
    $available = $this->deliveryService->listAvailableDeliveries();

    return response()->json([
      'data' => [
        'mine' => $mine->map(fn (Delivery $delivery) => $this->deliveryService->formatForCourier($delivery))->all(),
        'available' => $available->map(fn (Delivery $delivery) => $this->deliveryService->formatForCourier($delivery))->all(),
      ],
    ]);
  }

  /**
   * Prend en charge une livraison en attente.
   *
   * @param Request $request Requête authentifiée
   * @param string $deliveryId Identifiant livraison
   * @return JsonResponse Livraison assignée
   */
  public function accept(Request $request, string $deliveryId): JsonResponse
  {
    $delivery = Delivery::query()
      ->where('id', $deliveryId)
      ->with(['order.user', 'order.items', 'order.pickupPoint'])
      ->firstOrFail();

    $updated = $this->deliveryService->acceptDelivery($delivery, $request->user());

    return response()->json([
      'message' => 'Course prise en charge. Rendez-vous chez le client avec le QR code.',
      'data' => $this->deliveryService->formatForCourier($updated),
    ]);
  }

  /**
   * Scanne un QR code et retourne les infos commande.
   *
   * @param Request $request Requête avec token QR
   * @return JsonResponse Détails commande
   */
  public function scan(Request $request): JsonResponse
  {
    $request->validate([
      'token' => ['required', 'string'],
    ]);

    $result = $this->deliveryService->scanQrToken(
      $request->string('token')->toString(),
    );

    return response()->json(['data' => $result]);
  }

  /**
   * Confirme une livraison ou un retrait via QR avec photo preuve.
   *
   * @param Request $request Requête avec token et photo optionnelle
   * @return JsonResponse Résultat
   */
  public function confirm(Request $request): JsonResponse
  {
    $request->validate([
      'token' => ['required', 'string'],
      'comment' => ['nullable', 'string', 'max:500'],
      'photo' => ['nullable', 'image', 'max:5120'],
    ]);

    $result = $this->deliveryService->confirmByQr(
      $request->user(),
      $request->string('token')->toString(),
      $request->file('photo'),
      $request->input('comment'),
    );

    return response()->json([
      'message' => $result['message'],
      'data' => $result,
    ]);
  }
}
