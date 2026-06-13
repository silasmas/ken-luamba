<?php

namespace Database\Seeders;

use App\Enums\InternationalShippingPolicy;
use App\Enums\ShippingPricingMode;
use App\Models\ShippingCity;
use App\Models\ShippingSetting;
use App\Models\ShippingZone;
use App\Models\ShippingZoneCommune;
use Illuminate\Database\Seeder;

/**
 * Initialise les paramètres, villes et zones de livraison de démonstration.
 */
class ShippingSettingSeeder extends Seeder
{
  /**
   * Alimente la configuration livraison, les villes et les zones Kinshasa.
   */
  public function run(): void
  {
    ShippingSetting::query()->updateOrCreate(
      [],
      [
        'pricing_mode' => ShippingPricingMode::Zone,
        'fixed_amount' => 5000,
        'currency' => 'CDF',
        'domestic_country_code' => 'CD',
        'domestic_country_name' => 'République Démocratique du Congo',
        'international_policy' => InternationalShippingPolicy::Quote,
        'international_amount' => 75000,
        'international_message' => 'Pour une livraison hors RDC, notre équipe vous contactera pour établir un devis de fret.',
        'is_active' => true,
      ],
    );

    $kinshasa = ShippingCity::query()->updateOrCreate(
      ['name' => 'Kinshasa'],
      [
        'is_delivery_available' => true,
        'sort_order' => 1,
      ],
    );

    foreach ([
      ['name' => 'Lubumbashi', 'is_delivery_available' => false, 'sort_order' => 2],
      ['name' => 'Goma', 'is_delivery_available' => false, 'sort_order' => 3],
      ['name' => 'Bukavu', 'is_delivery_available' => false, 'sort_order' => 4],
      ['name' => 'Kisangani', 'is_delivery_available' => false, 'sort_order' => 5],
      ['name' => 'Mbuji-Mayi', 'is_delivery_available' => false, 'sort_order' => 6],
    ] as $cityData) {
      ShippingCity::query()->updateOrCreate(
        ['name' => $cityData['name']],
        [
          'is_delivery_available' => $cityData['is_delivery_available'],
          'sort_order' => $cityData['sort_order'],
        ],
      );
    }

    $zoneCentre = ShippingZone::query()->updateOrCreate(
      [
        'name' => 'Kinshasa — Centre',
        'shipping_city_id' => $kinshasa->id,
      ],
      [
        'amount' => 5000,
        'currency' => 'CDF',
        'sort_order' => 1,
        'is_active' => true,
      ],
    );

    $zonePeripherie = ShippingZone::query()->updateOrCreate(
      [
        'name' => 'Kinshasa — Périphérie',
        'shipping_city_id' => $kinshasa->id,
      ],
      [
        'amount' => 8000,
        'currency' => 'CDF',
        'sort_order' => 2,
        'is_active' => true,
      ],
    );

    foreach (['Gombe', 'Kinshasa', 'Barumbu', 'Lingwala'] as $commune) {
      ShippingZoneCommune::query()->updateOrCreate(
        [
          'shipping_zone_id' => $zoneCentre->id,
          'name' => $commune,
          'city' => 'Kinshasa',
        ],
      );
    }

    foreach (['Limete', 'Lemba', 'Ngaliema', 'Mont Ngafula'] as $commune) {
      ShippingZoneCommune::query()->updateOrCreate(
        [
          'shipping_zone_id' => $zonePeripherie->id,
          'name' => $commune,
          'city' => 'Kinshasa',
        ],
      );
    }
  }
}
