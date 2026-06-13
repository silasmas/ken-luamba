<?php

namespace App\Models;

use App\Enums\InternationalShippingPolicy;
use App\Enums\ShippingPricingMode;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Paramètres globaux des frais de livraison (enregistrement unique).
 */
class ShippingSetting extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'pricing_mode',
    'fixed_amount',
    'currency',
    'domestic_country_code',
    'domestic_country_name',
    'international_policy',
    'international_amount',
    'international_message',
    'is_active',
  ];

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'pricing_mode' => ShippingPricingMode::class,
      'fixed_amount' => 'decimal:2',
      'international_policy' => InternationalShippingPolicy::class,
      'international_amount' => 'decimal:2',
      'is_active' => 'boolean',
    ];
  }

  /**
   * Retourne l'enregistrement unique des paramètres de livraison.
   *
   * @return self Paramètres actifs ou valeurs par défaut
   */
  public static function instance(): self
  {
    return self::query()->firstOrCreate(
      [],
      [
        'pricing_mode' => ShippingPricingMode::Fixed,
        'fixed_amount' => 5000,
        'currency' => 'CDF',
        'domestic_country_code' => 'CD',
        'domestic_country_name' => 'République Démocratique du Congo',
        'international_policy' => InternationalShippingPolicy::Quote,
        'international_amount' => null,
        'international_message' => 'Pour une livraison hors RDC, notre équipe vous contactera pour établir un devis de fret.',
        'is_active' => true,
      ],
    );
  }
}
