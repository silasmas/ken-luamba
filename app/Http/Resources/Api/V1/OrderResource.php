<?php

namespace App\Http\Resources\Api\V1;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API pour une commande client.
 */
class OrderResource extends JsonResource
{
  /**
   * Transforme la commande en tableau JSON.
   *
   * @param Request $request Requête HTTP entrante
   * @return array<string, mixed> Données sérialisées
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'orderNumber' => $this->order_number,
      'status' => $this->status->value,
      'statusLabel' => $this->status->label(),
      'displayStatusLabel' => $this->displayStatusLabel(),
      'hasPhysicalItems' => $this->whenLoaded(
        'items',
        fn (): bool => $this->hasPhysicalItems(),
        false,
      ),
      'isDigitalOnly' => $this->whenLoaded(
        'items',
        fn (): bool => $this->isDigitalOnly(),
        false,
      ),
      'fulfillmentType' => $this->fulfillment_type?->value,
      'fulfillmentLabel' => $this->fulfillment_type?->label(),
      'pickupPoint' => $this->whenLoaded('pickupPoint', fn () => [
        'id' => $this->pickupPoint->id,
        'name' => $this->pickupPoint->name,
        'address' => $this->pickupPoint->address,
        'city' => $this->pickupPoint->city,
      ]),
      'shippingAddress' => $this->shipping_address,
      'subtotal' => (float) $this->subtotal,
      'discountAmount' => (float) $this->discount_amount,
      'shippingAmount' => (float) $this->shipping_amount,
      'total' => (float) $this->total,
      'currency' => $this->currency,
      'notes' => $this->notes,
      'paidAt' => $this->paid_at?->toIso8601String(),
      'items' => OrderItemResource::collection($this->whenLoaded('items')),
      'payment' => $this->whenLoaded('payment', fn () => [
        'id' => $this->payment->id,
        'status' => $this->payment->status->value,
        'statusLabel' => $this->payment->status->label(),
        'channel' => $this->payment->channel?->value,
        'channelLabel' => $this->payment->channel?->label(),
        'providerReference' => $this->payment->provider_reference,
      ]),
      'qrToken' => $this->whenLoaded(
        'qrCode',
        fn () => $this->hasPhysicalItems() ? $this->qrCode?->token : null,
      ),
      'courier' => $this->when(
        $this->relationLoaded('delivery') && $this->delivery?->courier_id !== null,
        function () {
          $courier = $this->delivery?->courier;

          if ($courier === null) {
            return null;
          }

          return [
            'id' => $courier->id,
            'fullName' => $courier->full_name,
            'phone' => $courier->phone,
            'avatarUrl' => MediaUrl::fromPath($courier->avatar_path),
          ];
        },
      ),
      'createdAt' => $this->created_at?->toIso8601String(),
    ];
  }
}
