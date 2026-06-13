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
      'quantity' => ['required', 'integer', 'min:1', 'max:99'],
    ];
  }
}
