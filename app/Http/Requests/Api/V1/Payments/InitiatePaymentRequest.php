<?php

namespace App\Http\Requests\Api\V1\Payments;

use App\Enums\PaymentChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valide l'initiation d'un paiement commande.
 */
class InitiatePaymentRequest extends FormRequest
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
   * Règles de validation du paiement.
   *
   * @return array<string, mixed> Règles Laravel
   */
  public function rules(): array
  {
    return [
      'channel' => ['required', Rule::enum(PaymentChannel::class)],
      'providerCode' => ['required_if:channel,mobile_money', 'nullable', 'string', 'max:32'],
      'phone' => ['required_if:channel,mobile_money', 'nullable', 'regex:/^243[0-9]{9}$/'],
    ];
  }
}
