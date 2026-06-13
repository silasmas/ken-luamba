<?php

namespace App\Services;

use App\Enums\FulfillmentType;
use App\Enums\InternationalShippingPolicy;
use App\Enums\ShippingPricingMode;
use App\Models\ShippingCity;
use App\Models\ShippingSetting;
use App\Models\ShippingZone;
use App\Models\ShippingZoneCommune;
use Illuminate\Validation\ValidationException;

/**
 * Service de calcul des frais de livraison (fixe, zones, international).
 */
class ShippingService
{
  /**
   * Retourne la configuration publique pour le checkout.
   *
   * @return array<string, mixed> Paramètres et zones actives
   */
  public function getPublicConfig(): array
  {
    $settings = ShippingSetting::instance();

    $cities = ShippingCity::query()
      ->orderBy('sort_order')
      ->orderBy('name')
      ->get()
      ->map(fn (ShippingCity $city): array => [
        'id' => $city->id,
        'name' => $city->name,
        'isDeliveryAvailable' => $city->is_delivery_available,
      ])
      ->all();

    $zones = ShippingZone::query()
      ->where('is_active', true)
      ->with(['communes' => fn ($query) => $query->orderBy('name'), 'city'])
      ->orderBy('sort_order')
      ->orderBy('name')
      ->get()
      ->map(fn (ShippingZone $zone): array => [
        'id' => $zone->id,
        'name' => $zone->name,
        'amount' => (float) $zone->amount,
        'currency' => $zone->currency,
        'cityId' => $zone->shipping_city_id,
        'cityName' => $zone->city?->name,
        'communes' => $zone->communes->map(fn (ShippingZoneCommune $commune): array => [
          'id' => $commune->id,
          'name' => $commune->name,
          'city' => $commune->city ?? $zone->city?->name,
        ])->all(),
      ])
      ->all();

    return [
      'isActive' => $settings->is_active,
      'pricingMode' => $settings->pricing_mode->value,
      'pricingModeLabel' => $settings->pricing_mode->label(),
      'fixedAmount' => (float) $settings->fixed_amount,
      'currency' => $settings->currency,
      'domesticCountryCode' => strtoupper($settings->domestic_country_code),
      'domesticCountryName' => $settings->domestic_country_name,
      'internationalPolicy' => $settings->international_policy->value,
      'internationalPolicyLabel' => $settings->international_policy->label(),
      'internationalAmount' => $settings->international_amount !== null
        ? (float) $settings->international_amount
        : null,
      'internationalMessage' => $settings->international_message,
      'cities' => $cities,
      'zones' => $zones,
    ];
  }

  /**
   * Calcule les frais de livraison pour une adresse donnée.
   *
   * @param FulfillmentType|null $fulfillmentType Mode de réception
   * @param array<string, mixed>|null $address Adresse de livraison
   * @return array<string, mixed> Devis de livraison
   */
  public function quote(?FulfillmentType $fulfillmentType, ?array $address): array
  {
    if ($fulfillmentType === null || $fulfillmentType === FulfillmentType::Pickup) {
      return $this->buildQuote(0, 'CDF', 'Retrait sur place', false, false, null, null);
    }

    if ($address === null || $address === []) {
      throw ValidationException::withMessages([
        'shippingAddress' => ['Adresse de livraison requise.'],
      ]);
    }

    $settings = ShippingSetting::instance();

    if (! $settings->is_active) {
      throw ValidationException::withMessages([
        'shipping' => ['La livraison est temporairement indisponible.'],
      ]);
    }

    $country = strtoupper(trim((string) ($address['country'] ?? $settings->domestic_country_code)));
    $domesticCode = strtoupper($settings->domestic_country_code);

    if ($country !== $domesticCode) {
      return $this->quoteInternational($settings, $country);
    }

    return $this->quoteDomestic($settings, $address);
  }

  /**
   * Calcule les frais pour une livraison nationale.
   *
   * @param ShippingSetting $settings Paramètres globaux
   * @param array<string, mixed> $address Adresse nationale
   * @return array<string, mixed> Devis national
   */
  private function quoteDomestic(ShippingSetting $settings, array $address): array
  {
    $city = $this->normalizeLocation((string) ($address['city'] ?? ''));

    if ($city === '') {
      throw ValidationException::withMessages([
        'shippingAddress.city' => ['La ville est requise pour calculer les frais de livraison.'],
      ]);
    }

    $shippingCity = $this->resolveDeliveryCity($city);

    if ($settings->pricing_mode === ShippingPricingMode::Fixed) {
      return $this->buildQuote(
        (float) $settings->fixed_amount,
        $settings->currency,
        'Livraison nationale — tarif fixe ('.$shippingCity->name.')',
        false,
        false,
        null,
        null,
      );
    }

    $commune = $this->normalizeLocation((string) ($address['commune'] ?? ''));

    if ($commune === '') {
      throw ValidationException::withMessages([
        'shippingAddress.commune' => ['La commune est requise pour calculer les frais de livraison.'],
      ]);
    }

    $zoneCommune = ShippingZoneCommune::query()
      ->whereHas('zone', fn ($query) => $query
        ->where('is_active', true)
        ->where('shipping_city_id', $shippingCity->id))
      ->get()
      ->first(function (ShippingZoneCommune $record) use ($commune, $city): bool {
        if ($this->normalizeLocation($record->name) !== $commune) {
          return false;
        }

        $recordCity = $record->city;

        if ($recordCity === null || $recordCity === '') {
          return true;
        }

        return $this->normalizeLocation($recordCity) === $city;
      });

    if ($zoneCommune === null) {
      throw ValidationException::withMessages([
        'shippingAddress.commune' => [
          'Aucune zone de livraison ne couvre cette commune à '.$shippingCity->name.'. Contactez le support.',
        ],
      ]);
    }

    $zone = $zoneCommune->zone;

    return $this->buildQuote(
      (float) $zone->amount,
      $zone->currency,
      'Zone : '.$zone->name.' ('.$shippingCity->name.')',
      false,
      false,
      $zone->id,
      $zone->name,
    );
  }

  /**
   * Vérifie que la ville est configurée et ouverte à la livraison.
   *
   * @param string $normalizedCity Nom de ville normalisé
   * @return ShippingCity Ville éligible
   */
  private function resolveDeliveryCity(string $normalizedCity): ShippingCity
  {
    $shippingCity = ShippingCity::query()
      ->get()
      ->first(fn (ShippingCity $record): bool => $this->normalizeLocation($record->name) === $normalizedCity);

    if ($shippingCity === null) {
      throw ValidationException::withMessages([
        'shippingAddress.city' => ['Cette ville n\'est pas couverte par notre politique de livraison nationale.'],
      ]);
    }

    if (! $shippingCity->is_delivery_available) {
      throw ValidationException::withMessages([
        'shippingAddress.city' => ['La livraison n\'est pas disponible pour '.$shippingCity->name.' pour le moment.'],
      ]);
    }

    return $shippingCity;
  }

  /**
   * Calcule les frais pour une livraison internationale.
   *
   * @param ShippingSetting $settings Paramètres globaux
   * @param string $countryCode Code pays ISO
   * @return array<string, mixed> Devis international
   */
  private function quoteInternational(ShippingSetting $settings, string $countryCode): array
  {
    return match ($settings->international_policy) {
      InternationalShippingPolicy::Fixed => $this->buildQuote(
        (float) ($settings->international_amount ?? 0),
        $settings->currency,
        'Livraison internationale — tarif fixe',
        true,
        false,
        null,
        null,
      ),
      InternationalShippingPolicy::Quote => $this->buildQuote(
        0,
        $settings->currency,
        'Livraison internationale — sur devis',
        true,
        true,
        null,
        null,
        $settings->international_message
          ?? 'Notre équipe vous contactera pour confirmer les frais de fret internationaux.',
      ),
      InternationalShippingPolicy::Unavailable => throw ValidationException::withMessages([
        'shippingAddress.country' => [
          $settings->international_message
            ?? 'La livraison hors du pays n\'est pas disponible pour le moment.',
        ],
      ]),
    };
  }

  /**
   * Construit la structure de devis standardisée.
   *
   * @param float $amount Montant des frais
   * @param string $currency Devise
   * @param string $label Libellé affiché
   * @param bool $isInternational Livraison hors pays
   * @param bool $requiresQuote Devis manuel requis
   * @param string|null $zoneId Identifiant zone
   * @param string|null $zoneName Nom zone
   * @param string|null $policyMessage Message politique fret
   * @return array<string, mixed> Devis formaté
   */
  private function buildQuote(
    float $amount,
    string $currency,
    string $label,
    bool $isInternational,
    bool $requiresQuote,
    ?string $zoneId,
    ?string $zoneName,
    ?string $policyMessage = null,
  ): array {
    return [
      'amount' => $amount,
      'currency' => $currency,
      'label' => $label,
      'isInternational' => $isInternational,
      'requiresQuote' => $requiresQuote,
      'zoneId' => $zoneId,
      'zoneName' => $zoneName,
      'policyMessage' => $policyMessage,
    ];
  }

  /**
   * Normalise un libellé de commune ou ville pour comparaison.
   *
   * @param string $value Texte saisi
   * @return string Valeur normalisée
   */
  public function normalizeLocation(string $value): string
  {
    $normalized = mb_strtolower(trim($value));

    return preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
  }
}
