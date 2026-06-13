<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide la soumission d'un témoignage lecteur.
 */
class StoreBookReviewRequest extends FormRequest
{
  /**
   * Détermine si l'utilisateur peut soumettre un avis.
   *
   * @return bool Toujours vrai (route protégée Sanctum)
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Règles de validation du témoignage.
   *
   * @return array<string, mixed> Règles Laravel
   */
  public function rules(): array
  {
    return [
      'rating' => ['required', 'integer', 'min:1', 'max:5'],
      'content' => ['required', 'string', 'min:20', 'max:2000'],
      'authorRole' => ['nullable', 'string', 'max:120'],
    ];
  }
}
