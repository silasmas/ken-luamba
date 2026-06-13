<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Orders\CreateOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Services\DeliveryService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Contrôleur API pour les commandes client.
 */
class OrderController extends Controller
{
  /**
   * Initialise le contrôleur commandes.
   *
   * @param OrderService $orderService Service de création de commandes
   * @param DeliveryService $deliveryService Service livraison client
   */
  public function __construct(
    private readonly OrderService $orderService,
    private readonly DeliveryService $deliveryService,
  ) {}

  /**
   * Liste les commandes de l'utilisateur connecté.
   *
   * @param Request $request Requête HTTP
   * @return AnonymousResourceCollection Commandes paginées
   */
  public function index(Request $request): AnonymousResourceCollection
  {
    $orders = Order::query()
      ->where('user_id', $request->user()->id)
      ->with(['items', 'payment', 'pickupPoint', 'qrCode', 'delivery.courier'])
      ->latest()
      ->paginate(15);

    return OrderResource::collection($orders);
  }

  /**
   * Affiche une commande par son numéro.
   *
   * @param Request $request Requête HTTP
   * @param string $orderNumber Numéro de commande
   * @return OrderResource Commande détaillée
   */
  public function show(Request $request, string $orderNumber): OrderResource
  {
    $order = Order::query()
      ->where('order_number', $orderNumber)
      ->where('user_id', $request->user()->id)
      ->with(['items', 'payment', 'pickupPoint', 'qrCode', 'delivery.courier'])
      ->firstOrFail();

    return new OrderResource($order);
  }

  /**
   * Crée une commande depuis le panier courant.
   *
   * @param CreateOrderRequest $request Données validées
   * @return JsonResponse Commande créée
   */
  public function store(CreateOrderRequest $request): JsonResponse
  {
    $order = $this->orderService->createFromCart(
      $request,
      $request->user(),
      $request->validated(),
    );

    return response()->json([
      'message' => 'Commande créée. Procédez au paiement.',
      'data' => new OrderResource($order),
    ], 201);
  }

  /**
   * Le client confirme la réception de sa commande.
   *
   * @param Request $request Requête authentifiée
   * @param string $orderNumber Numéro de commande
   * @return JsonResponse Commande confirmée
   */
  public function confirmReceipt(Request $request, string $orderNumber): JsonResponse
  {
    $order = Order::query()
      ->where('order_number', $orderNumber)
      ->where('user_id', $request->user()->id)
      ->firstOrFail();

    $updated = $this->deliveryService->confirmReceipt($order, $request->user());

    return response()->json([
      'message' => 'Réception confirmée. Merci !',
      'data' => new OrderResource($updated->load(['items', 'payment', 'qrCode', 'delivery.courier'])),
    ]);
  }

  /**
   * Le client conteste une livraison.
   *
   * @param Request $request Requête avec motif optionnel
   * @param string $orderNumber Numéro de commande
   * @return JsonResponse Litige enregistré
   */
  public function disputeDelivery(Request $request, string $orderNumber): JsonResponse
  {
    $request->validate([
      'reason' => ['nullable', 'string', 'max:1000'],
    ]);

    $order = Order::query()
      ->where('order_number', $orderNumber)
      ->where('user_id', $request->user()->id)
      ->firstOrFail();

    $updated = $this->deliveryService->disputeDelivery(
      $order,
      $request->user(),
      $request->input('reason'),
    );

    return response()->json([
      'message' => 'Litige enregistré. Notre équipe vous contactera.',
      'data' => new OrderResource($updated->load(['items', 'payment', 'delivery.courier'])),
    ]);
  }
}
