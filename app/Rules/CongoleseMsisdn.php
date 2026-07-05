<?php

namespace App\Rules;

use App\Support\PhoneNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valide un numéro congolais au format 243XXXXXXXXX.
 */
class CongoleseMsisdn implements ValidationRule
{
  /**
   * Vérifie le format MSISDN congolais.
   *
   * @param string $attribute Nom du champ
   * @param mixed $value Valeur soumise
   * @param Closure $fail Callback d'échec
   * @return void
   */
  public function validate(string $attribute, mixed $value, Closure $fail): void
  {
    $normalized = PhoneNormalizer::normalize(is_string($value) ? $value : null);

    if (! PhoneNormalizer::isValid($normalized)) {
      $fail('Numéro invalide. Utilisez le format 243XXXXXXXXX (12 chiffres).');
    }
  }
}
