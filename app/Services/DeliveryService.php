<?php

namespace App\Services;

use App\Enums\DeliveryStatus;
use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Delivery;
use App\Models\DeliveryProof;
use App\Models\Order;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Service de gestion des livraisons, retraits et scan QR livreur.
 */
class DeliveryService
{
  /**
   * Initialise le service livraison.
   *
   * @param OrderNotificationService $notificationService Service de notifications email
   */
  public function __construct(
    private readonly OrderNotificationService $notificationService,
  ) {}

  /**
   * Crée une livraison pour une commande payée contenant des articles physiques.
   *
   * @param Order $order Commande payée
   * @return Delivery|null Livraison créée ou null si commande 100 % numérique
   */
  public function createForOrder(Order $order): ?Delivery
  {
    $order->loadMissing('items');

    $hasPhysical = $order->items->contains(
      fn ($item) => ! $item->format_type->isDigital()
    );

    if (! $hasPhysical) {
      return null;
    }

    return Delivery::query()->updateOrCreate(
      ['order_id' => $order->id],
      ['status' => DeliveryStatus::Pending],
    );
  }

  /**
   * Assigne un livreur à une livraison.
   *
   * @param Delivery $delivery Livraison cible
   * @param User $courier Livreur
   * @return Delivery Livraison mise à jour
   */
  public function assignCourier(Delivery $delivery, User $courier): Delivery
  {
    if ($courier->role !== UserRole::Courier && $courier->role !== UserRole::Admin) {
      throw ValidationException::withMessages([
        'courier' => ['Utilisateur non autorisé comme livreur.'],
      ]);
    }

    $delivery->update([
      'courier_id' => $courier->id,
      'status' => DeliveryStatus::Assigned,
      'assigned_at' => now(),
    ]);

    $delivery->order?->update(['status' => OrderStatus::OutForDelivery]);

    $fresh = $delivery->fresh(['order.items', 'order.user', 'order.pickupPoint', 'courier']);

    $this->notificationService->afterCommit(function () use ($fresh): void {
      $this->notificationService->notifyDeliveryAssigned($fresh);
    });

    return $fresh;
  }

  /**
   * Résout une commande via scan du token QR.
   *
   * @param string $token Token QR scanné
   * @return array<string, mixed> Informations commande pour le livreur
   */
  public function scanQrToken(string $token): array
  {
    $qrCode = QrCode::query()
      ->where('token', $token)
      ->with(['order.user', 'order.items', 'order.pickupPoint', 'order.delivery'])
      ->first();

    if ($qrCode === null) {
      throw ValidationException::withMessages([
        'token' => ['QR code invalide.'],
      ]);
    }

    $order = $qrCode->order;

    if ($order === null) {
      throw ValidationException::withMessages([
        'token' => ['Commande introuvable.'],
      ]);
    }

    return [
      'orderId' => $order->id,
      'orderNumber' => $order->order_number,
      'status' => $order->status->value,
      'statusLabel' => $order->status->label(),
      'customerName' => $order->user?->full_name,
      'fulfillmentType' => $order->fulfillment_type?->value,
      'fulfillmentLabel' => $order->fulfillment_type?->label(),
      'shippingAddress' => $order->shipping_address,
      'pickupPoint' => $order->pickupPoint ? [
        'name' => $order->pickupPoint->name,
        'address' => $order->pickupPoint->address,
        'city' => $order->pickupPoint->city,
      ] : null,
      'items' => $order->items->map(fn ($item) => [
        'bookTitle' => $item->book_title,
        'formatLabel' => $item->format_type->label(),
        'quantity' => $item->quantity,
      ])->all(),
      'deliveryId' => $order->delivery?->id,
      'qrUsed' => $qrCode->is_used,
    ];
  }

  /**
   * Confirme une livraison ou un retrait via scan QR.
   *
   * @param User $courier Livreur connecté
   * @param string $token Token QR
   * @param UploadedFile|null $photo Photo preuve optionnelle
   * @param string|null $comment Commentaire livreur
   * @return array<string, mixed> Résultat de confirmation
   */
  public function confirmByQr(
    User $courier,
    string $token,
    ?UploadedFile $photo = null,
    ?string $comment = null,
  ): array {
    if ($courier->role !== UserRole::Courier && $courier->role !== UserRole::Admin) {
      throw ValidationException::withMessages([
        'courier' => ['Accès livreur requis.'],
      ]);
    }

    return DB::transaction(function () use ($courier, $token, $photo, $comment): array {
      $qrCode = QrCode::query()->where('token', $token)->with('order.delivery')->firstOrFail();
      $order = $qrCode->order;

      if ($order === null) {
        throw ValidationException::withMessages([
          'token' => ['Commande introuvable.'],
        ]);
      }

      $delivery = $order->delivery ?? $this->createForOrder($order);

      if ($delivery === null) {
        throw ValidationException::withMessages([
          'order' => ['Cette commande ne nécessite pas de livraison physique.'],
        ]);
      }

      $deliveryStatus = $order->fulfillment_type === FulfillmentType::Pickup
        ? DeliveryStatus::PickedUp
        : DeliveryStatus::Delivered;

      $orderStatus = OrderStatus::DeliveredByCourier;

      $delivery->update([
        'courier_id' => $courier->id,
        'status' => $deliveryStatus,
        'delivered_at' => now(),
      ]);

      $order->update(['status' => $orderStatus]);

      $qrCode->update([
        'is_used' => true,
        'used_at' => now(),
      ]);

      if ($photo !== null) {
        $path = $photo->store('delivery-proofs', 'local');
        DeliveryProof::query()->create([
          'delivery_id' => $delivery->id,
          'uploaded_by' => $courier->id,
          'photo_path' => $path,
          'comment' => $comment,
        ]);
      }

      $order->refresh()->load(['user', 'delivery.courier']);

      $this->notificationService->afterCommit(function () use ($order): void {
        $this->notificationService->notifyDeliveryConfirmedByCourier($order);
      });

      return [
        'success' => true,
        'message' => 'Livraison confirmée avec succès.',
        'orderNumber' => $order->order_number,
        'deliveryStatus' => $deliveryStatus->value,
      ];
    });
  }

  /**
   * Le client confirme la réception de sa commande.
   *
   * @param Order $order Commande du client
   * @param User $user Client connecté
   * @return Order Commande mise à jour
   */
  public function confirmReceipt(Order $order, User $user): Order
  {
    if ($order->user_id !== $user->id) {
      throw ValidationException::withMessages([
        'order' => ['Commande non autorisée.'],
      ]);
    }

    if ($order->status !== OrderStatus::DeliveredByCourier) {
      throw ValidationException::withMessages([
        'order' => ['La commande ne peut pas être confirmée dans cet état.'],
      ]);
    }

    $order->update([
      'status' => OrderStatus::Completed,
      'completed_at' => now(),
    ]);

    $fresh = $order->fresh(['items', 'payment', 'qrCode', 'delivery.courier', 'user']);

    $this->notificationService->afterCommit(function () use ($fresh): void {
      $this->notificationService->notifyDeliveryConfirmedByClient($fresh);
    });

    return $fresh;
  }

  /**
   * Le client conteste une livraison.
   *
   * @param Order $order Commande du client
   * @param User $user Client connecté
   * @param string|null $reason Motif du litige
   * @return Order Commande mise à jour
   */
  public function disputeDelivery(Order $order, User $user, ?string $reason = null): Order
  {
    if ($order->user_id !== $user->id) {
      throw ValidationException::withMessages([
        'order' => ['Commande non autorisée.'],
      ]);
    }

    if ($order->status !== OrderStatus::DeliveredByCourier) {
      throw ValidationException::withMessages([
        'order' => ['Litige impossible dans cet état.'],
      ]);
    }

    $order->update(['status' => OrderStatus::DeliveryDisputed]);
    $order->delivery?->update([
      'status' => DeliveryStatus::Disputed,
      'notes' => $reason,
    ]);

    $fresh = $order->fresh(['items', 'payment', 'delivery.courier', 'user']);

    $this->notificationService->afterCommit(function () use ($fresh, $reason): void {
      $this->notificationService->notifyDeliveryDisputed($fresh, $reason);
    });

    return $fresh;
  }

  /**
   * Liste les livraisons assignées à un livreur.
   *
   * @param User $courier Livreur connecté
   * @return \Illuminate\Database\Eloquent\Collection<int, Delivery> Livraisons
   */
  public function listForCourier(User $courier)
  {
    return Delivery::query()
      ->where('courier_id', $courier->id)
      ->whereNotIn('status', [DeliveryStatus::Delivered, DeliveryStatus::PickedUp, DeliveryStatus::Disputed])
      ->with(['order.user', 'order.items', 'order.pickupPoint'])
      ->orderByDesc('assigned_at')
      ->get();
  }

  /**
   * Liste les livraisons en attente non assignées.
   *
   * @return \Illuminate\Database\Eloquent\Collection<int, Delivery> Courses disponibles
   */
  public function listAvailableDeliveries()
  {
    return Delivery::query()
      ->whereNull('courier_id')
      ->where('status', DeliveryStatus::Pending)
      ->with(['order.user', 'order.items', 'order.pickupPoint'])
      ->latest()
      ->get();
  }

  /**
   * Permet à un livreur de prendre en charge une livraison en attente.
   *
   * @param Delivery $delivery Livraison cible
   * @param User $courier Livreur connecté
   * @return Delivery Livraison assignée
   */
  public function acceptDelivery(Delivery $delivery, User $courier): Delivery
  {
    if ($delivery->courier_id !== null) {
      throw ValidationException::withMessages([
        'delivery' => ['Cette course est déjà assignée à un autre livreur.'],
      ]);
    }

    if ($delivery->status !== DeliveryStatus::Pending) {
      throw ValidationException::withMessages([
        'delivery' => ['Cette livraison ne peut plus être prise en charge.'],
      ]);
    }

    return $this->assignCourier($delivery, $courier);
  }

  /**
   * Formate une livraison pour l'API livreur.
   *
   * @param Delivery $delivery Livraison source
   * @return array<string, mixed> Données formatées
   */
  public function formatForCourier(Delivery $delivery): array
  {
    return (new \App\Http\Resources\Api\V1\CourierDeliveryResource($delivery))->resolve();
  }

  /**
   * Synchronise la réception article par article depuis l'admin.
   *
   * @param Order $order Commande cible
   * @param list<string> $receivedItemIds Identifiants des lignes reçues
   * @return Order Commande mise à jour
   */
  public function syncPhysicalItemsReceiptByAdmin(Order $order, array $receivedItemIds): Order
  {
    if (! $order->hasPhysicalItems()) {
      throw ValidationException::withMessages([
        'order' => ['Cette commande ne contient pas de livre physique.'],
      ]);
    }

    if (in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Refunded, OrderStatus::PendingPayment], true)) {
      throw ValidationException::withMessages([
        'order' => ['Impossible de mettre à jour la réception dans cet état de commande.'],
      ]);
    }

    $order->loadMissing('items');
    $physicalItems = $order->items->filter(fn ($item) => $item->isPhysical());
    $allowedIds = $physicalItems->pluck('id')->all();
    $selectedIds = array_values(array_intersect($receivedItemIds, $allowedIds));

    foreach ($physicalItems as $item) {
      $shouldBeReceived = in_array($item->id, $selectedIds, true);

      if ($shouldBeReceived && $item->received_at === null) {
        $item->update(['received_at' => now()]);
      }

      if (! $shouldBeReceived && $item->received_at !== null) {
        $item->update(['received_at' => null]);
      }
    }

    $order->refresh()->loadMissing(['items', 'delivery', 'payment']);

    if ($physicalItems->every(fn ($item) => $item->fresh()->received_at !== null)) {
      return $this->markBooksReceivedByAdmin($order);
    }

    return $this->markBooksPartiallyReceivedByAdmin($order);
  }

  /**
   * Marque une commande physique comme livre reçu depuis l'admin.
   *
   * @param Order $order Commande cible
   * @return Order Commande mise à jour
   */
  public function markBooksReceivedByAdmin(Order $order): Order
  {
    if (! $order->hasPhysicalItems()) {
      throw ValidationException::withMessages([
        'order' => ['Cette commande ne contient pas de livre physique.'],
      ]);
    }

    if (in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Refunded, OrderStatus::PendingPayment], true)) {
      throw ValidationException::withMessages([
        'order' => ['Impossible de marquer comme reçu dans cet état de commande.'],
      ]);
    }

    return DB::transaction(function () use ($order): Order {
      $order->loadMissing('items');

      foreach ($order->items as $item) {
        if ($item->isPhysical() && $item->received_at === null) {
          $item->update(['received_at' => now()]);
        }
      }

      $delivery = $order->delivery ?? $this->createForOrder($order);

      if ($delivery === null) {
        throw ValidationException::withMessages([
          'order' => ['Livraison introuvable pour cette commande.'],
        ]);
      }

      $deliveryStatus = $order->fulfillment_type === FulfillmentType::Pickup
        ? DeliveryStatus::PickedUp
        : DeliveryStatus::Delivered;

      $delivery->update([
        'status' => $deliveryStatus,
        'delivered_at' => now(),
      ]);

      $order->update([
        'status' => OrderStatus::Completed,
        'completed_at' => now(),
      ]);

      return $order->fresh(['user', 'items', 'delivery', 'payment']);
    });
  }

  /**
   * Conserve une réception partielle sans clôturer la commande.
   *
   * @param Order $order Commande cible
   * @return Order Commande mise à jour
   */
  private function markBooksPartiallyReceivedByAdmin(Order $order): Order
  {
    return DB::transaction(function () use ($order): Order {
      $delivery = $order->delivery ?? $this->createForOrder($order);

      if ($delivery !== null) {
        $delivery->update([
          'status' => DeliveryStatus::Pending,
          'delivered_at' => null,
        ]);
      }

      $order->loadMissing('payment');

      $nextStatus = $order->payment?->status === PaymentStatus::Completed
        ? OrderStatus::Paid
        : OrderStatus::Processing;

      $order->update([
        'status' => $nextStatus,
        'completed_at' => null,
      ]);

      return $order->fresh(['user', 'items', 'delivery', 'payment']);
    });
  }

  /**
   * Marque une commande physique comme livre non reçu depuis l'admin.
   *
   * @param Order $order Commande cible
   * @return Order Commande mise à jour
   */
  public function markBooksNotReceivedByAdmin(Order $order): Order
  {
    if (! $order->hasPhysicalItems()) {
      throw ValidationException::withMessages([
        'order' => ['Cette commande ne contient pas de livre physique.'],
      ]);
    }

    return DB::transaction(function () use ($order): Order {
      $order->loadMissing('items');

      foreach ($order->items as $item) {
        if ($item->isPhysical() && $item->received_at !== null) {
          $item->update(['received_at' => null]);
        }
      }

      $delivery = $order->delivery ?? $this->createForOrder($order);

      if ($delivery !== null) {
        $delivery->update([
          'status' => DeliveryStatus::Pending,
          'courier_id' => null,
          'assigned_at' => null,
          'delivered_at' => null,
        ]);
      }

      $order->loadMissing('payment');

      $nextStatus = $order->payment?->status === PaymentStatus::Completed
        ? OrderStatus::Paid
        : OrderStatus::Processing;

      $order->update([
        'status' => $nextStatus,
        'completed_at' => null,
      ]);

      return $order->fresh(['user', 'items', 'delivery', 'payment']);
    });
  }
}
