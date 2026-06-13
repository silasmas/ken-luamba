<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

/**
 * Gestion des opérateurs Mobile Money (liste publique et validation MSISDN).
 */
class MobileMoneyOperatorService
{
  /**
   * Retourne la liste des opérateurs disponibles pour le checkout.
   *
   * @return array<int, array<string, string>> Opérateurs configurés
   */
  public function listForApi(): array
  {
    return collect(config('flexpay.flexpay_mobile_providers', []))
      ->map(fn (array $provider): array => [
        'code' => (string) ($provider['code'] ?? $provider['type'] ?? ''),
        'label' => (string) ($provider['label'] ?? ''),
        'msisdnPattern' => (string) ($provider['msisdn_regex'] ?? ''),
        'phoneHint' => (string) ($provider['phone_hint'] ?? '243XXXXXXXXX'),
      ])
      ->filter(fn (array $provider): bool => $provider['code'] !== '' && $provider['label'] !== '')
      ->values()
      ->all();
  }

  /**
   * Valide que le numéro correspond à l'opérateur choisi.
   *
   * @param string $providerCode Code opérateur (mpesa, orange…)
   * @param string $phone Numéro au format 243XXXXXXXXX
   * @return array<string, string> Opérateur validé
   */
  public function validate(string $providerCode, string $phone): array
  {
    $provider = $this->findByCode($providerCode);

    if ($provider === null) {
      throw ValidationException::withMessages([
        'providerCode' => ['Opérateur mobile invalide.'],
      ]);
    }

    if (! preg_match('/^243[0-9]{9}$/', $phone)) {
      throw ValidationException::withMessages([
        'phone' => ['Numéro invalide. Utilisez le format 243XXXXXXXXX (12 chiffres).'],
      ]);
    }

    $regex = (string) ($provider['msisdn_regex'] ?? '');

    if ($regex !== '' && ! preg_match('/'.$regex.'/', $phone)) {
      throw ValidationException::withMessages([
        'phone' => [
          'Ce numéro ne correspond pas à '.$provider['label'].'. Vérifiez l\'opérateur sélectionné.',
        ],
      ]);
    }

    return $provider;
  }

  /**
   * Retourne un opérateur par son code.
   *
   * @param string $providerCode Code opérateur
   * @return array<string, mixed>|null Configuration opérateur
   */
  private function findByCode(string $providerCode): ?array
  {
    $provider = collect(config('flexpay.flexpay_mobile_providers', []))
      ->first(function (array $item) use ($providerCode): bool {
        $code = (string) ($item['code'] ?? $item['type'] ?? '');

        return $code === $providerCode;
      });

    return is_array($provider) ? $provider : null;
  }
}
