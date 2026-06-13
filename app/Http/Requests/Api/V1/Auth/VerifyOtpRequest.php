<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide la vérification d'un code OTP.
 */
class VerifyOtpRequest extends FormRequest
{
  /**
   * Détermine si l'utilisateur peut soumettre cette requête.
   *
   * @return bool Toujours true pour la vérification publique
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Règles de validation du code OTP.
   *
   * @return array<string, mixed> Règles Laravel
   */
  public function rules(): array
  {
    return [
      'email' => ['required', 'email', 'max:255'],
      'code' => ['required', 'string', 'size:6'],
      'type' => ['required', 'in:register,login'],
    ];
  }
}
