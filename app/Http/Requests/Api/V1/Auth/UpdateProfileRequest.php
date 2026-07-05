<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Rules\CongoleseMsisdn;
use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide la mise à jour du profil client.
 */
class UpdateProfileRequest extends FormRequest
{
  /**
   * Autorise uniquement les utilisateurs connectés.
   *
   * @return bool Accès autorisé
   */
  public function authorize(): bool
  {
    return $this->user() !== null;
  }

  /**
   * Normalise les téléphones avant validation.
   *
   * @return void
   */
  protected function prepareForValidation(): void
  {
    $merge = [];

    if ($this->has('phone')) {
      $merge['phone'] = PhoneNormalizer::normalize($this->input('phone'));
    }

    if ($this->has('secondaryPhone')) {
      $merge['secondaryPhone'] = PhoneNormalizer::normalize($this->input('secondaryPhone'));
    }

    if ($merge !== []) {
      $this->merge($merge);
    }
  }

  /**
   * Règles de validation du profil.
   *
   * @return array<string, mixed> Règles
   */
  public function rules(): array
  {
    return [
      'fullName' => ['sometimes', 'string', 'max:120'],
      'phone' => ['sometimes', 'nullable', 'string', new CongoleseMsisdn()],
      'secondaryPhone' => ['sometimes', 'nullable', 'string', new CongoleseMsisdn()],
      'profileAddress' => ['sometimes', 'nullable', 'array'],
      'profileAddress.street' => ['nullable', 'string', 'max:255'],
      'profileAddress.city' => ['nullable', 'string', 'max:120'],
      'profileAddress.commune' => ['nullable', 'string', 'max:120'],
      'profileAddress.country' => ['nullable', 'string', 'max:2'],
      'profileAddress.phone' => ['nullable', 'string', 'max:30'],
      'deliveryAddress' => ['sometimes', 'nullable', 'array'],
      'deliveryAddress.street' => ['nullable', 'string', 'max:255'],
      'deliveryAddress.city' => ['nullable', 'string', 'max:120'],
      'deliveryAddress.commune' => ['nullable', 'string', 'max:120'],
      'deliveryAddress.country' => ['nullable', 'string', 'max:2'],
      'deliveryAddress.phone' => ['nullable', 'string', 'max:30'],
    ];
  }
}
