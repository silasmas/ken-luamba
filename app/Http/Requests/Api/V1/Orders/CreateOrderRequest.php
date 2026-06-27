<?php

namespace App\Http\Requests\Api\V1\Orders;

use App\Enums\FulfillmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valide la création d'une commande depuis le panier.
 */
class CreateOrderRequest extends FormRequest
{
  /**
   * Détermine si la requête est autorisée.
   *
   * @return bool Utilisateur connecté requis
   */
  public function authorize(): bool
  {
    return $this->user() !== null;
  }

  /**
   * Règles de validation de la commande.
   *
   * @return array<string, mixed> Règles Laravel
   */
  public function rules(): array
  {
    return [
      'fulfillmentType' => ['nullable', Rule::enum(FulfillmentType::class)],
      'pickupPointId' => ['nullable', 'uuid', 'exists:pickup_points,id'],
      'shippingAddress' => ['nullable', 'array'],
      'shippingAddress.street' => ['required_with:shippingAddress', 'string', 'max:255'],
      'shippingAddress.city' => ['required_with:shippingAddress', 'string', 'max:120'],
      'shippingAddress.commune' => ['nullable', 'string', 'max:120'],
      'shippingAddress.country' => ['required_with:shippingAddress', 'string', 'size:2'],
      'shippingAddress.phone' => ['required_with:shippingAddress', 'string', 'max:20'],
      'notes' => ['nullable', 'string', 'max:1000'],
      'extraContributionAmount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
    ];
  }
}
