<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Delivery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API pour une livraison côté livreur.
 */
class CourierDeliveryResource extends JsonResource
{
  /**
   * Transforme la livraison en tableau JSON pour l'espace livreur.
   *
   * @param Request $request Requête HTTP entrante
   * @return array<string, mixed> Données sérialisées
   */
  public function toArray(Request $request): array
  {
    /** @var Delivery $delivery */
    $delivery = $this->resource;
    $order = $delivery->order;

    return [
      'id' => $delivery->id,
      'status' => $delivery->status->value,
      'statusLabel' => $delivery->status->label(),
      'assignedAt' => $delivery->assigned_at?->toIso8601String(),
      'deliveredAt' => $delivery->delivered_at?->toIso8601String(),
      'orderNumber' => $order?->order_number,
      'orderStatus' => $order?->status->value,
      'orderStatusLabel' => $order?->status->label(),
      'customerName' => $order?->user?->full_name,
      'customerPhone' => $order?->user?->phone ?? ($order?->shipping_address['phone'] ?? null),
      'fulfillmentType' => $order?->fulfillment_type?->value,
      'fulfillmentLabel' => $order?->fulfillment_type?->label(),
      'shippingAddress' => $order?->shipping_address,
      'pickupPoint' => $order?->pickupPoint ? [
        'name' => $order->pickupPoint->name,
        'address' => $order->pickupPoint->address,
        'city' => $order->pickupPoint->city,
        'phone' => $order->pickupPoint->phone,
      ] : null,
      'items' => $order?->items->map(fn ($item) => [
        'bookTitle' => $item->book_title,
        'formatLabel' => $item->format_type->label(),
        'quantity' => $item->quantity,
      ])->all() ?? [],
      'notes' => $delivery->notes,
    ];
  }
}
