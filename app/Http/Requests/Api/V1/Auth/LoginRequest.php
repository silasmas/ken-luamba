<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide une demande de connexion par OTP.
 */
class LoginRequest extends FormRequest
{
  /**
   * Détermine si l'utilisateur peut soumettre cette requête.
   *
   * @return bool Toujours true pour la connexion publique
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Règles de validation de la connexion.
   *
   * @return array<string, mixed> Règles Laravel
   */
  public function rules(): array
  {
    return [
      'email' => ['required', 'email', 'max:255'],
    ];
  }
}
