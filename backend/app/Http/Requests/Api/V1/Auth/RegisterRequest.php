<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide une demande d'inscription par OTP.
 */
class RegisterRequest extends FormRequest
{
  /**
   * Détermine si l'utilisateur peut soumettre cette requête.
   *
   * @return bool Toujours true pour l'inscription publique
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Règles de validation de l'inscription.
   *
   * @return array<string, mixed> Règles Laravel
   */
  public function rules(): array
  {
    return [
      'email' => ['required', 'email', 'max:255'],
      'fullName' => ['required', 'string', 'max:255'],
    ];
  }
}
