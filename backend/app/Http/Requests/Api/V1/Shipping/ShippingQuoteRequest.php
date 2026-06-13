<?php

namespace App\Http\Requests\Api\V1\Shipping;

use App\Enums\FulfillmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valide une demande de devis de livraison.
 */
class ShippingQuoteRequest extends FormRequest
{
  /**
   * Détermine si la requête est autorisée.
   *
   * @return bool Accès public
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Règles de validation du devis livraison.
   *
   * @return array<string, mixed> Règles Laravel
   */
  public function rules(): array
  {
    return [
      'fulfillmentType' => ['required', Rule::enum(FulfillmentType::class)],
      'country' => ['nullable', 'string', 'size:2'],
      'city' => ['nullable', 'string', 'max:120'],
      'commune' => ['nullable', 'string', 'max:120'],
    ];
  }
}
