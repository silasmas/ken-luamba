<?php

namespace App\Http\Requests\Api\V1\Cart;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide l'ajout d'un article au panier.
 */
class AddCartItemRequest extends FormRequest
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
   * Règles de validation de l'ajout au panier.
   *
   * @return array<string, mixed> Règles Laravel
   */
  public function rules(): array
  {
    return [
      'bookFormatId' => ['required', 'uuid', 'exists:book_formats,id'],
      'quantity' => ['sometimes', 'integer', 'min:1', 'max:99'],
    ];
  }
}
