<?php

namespace App\Http\Requests\Api\V1\Cart;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide la mise à jour d'une ligne panier.
 */
class UpdateCartItemRequest extends FormRequest
{
  /**
   * Détermine si la requête est autorisée.
   *
   * @return bool Toujours true (invité ou connecté)
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Règles de validation de la mise à jour.
   *
   * @return array<string, mixed> Règles Laravel
   */
  public function rules(): array
  {
    return [
      'quantity' => ['required', 'integer', 'min:1'],
    ];
  }

  /**
   * Messages de validation en français pour l'API panier.
   *
   * @return array<string, string> Messages personnalisés
   */
  public function messages(): array
  {
    return [
      'quantity.required' => 'Indiquez une quantité.',
      'quantity.integer' => 'La quantité doit être un nombre entier.',
      'quantity.min' => 'La quantité minimale est de 1 exemplaire.',
    ];
  }
}
