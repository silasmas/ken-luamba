<?php

namespace Database\Seeders;

use App\Models\PickupPoint;
use Illuminate\Database\Seeder;

/**
 * Alimente les points de retrait par défaut.
 */
class PickupPointSeeder extends Seeder
{
  /**
   * Crée les points de retrait de démonstration.
   */
  public function run(): void
  {
    PickupPoint::query()->updateOrCreate(
      ['name' => 'Église Ken Luamba — Kinshasa'],
      [
        'address' => 'Avenue de la Libération, Commune de la Gombe',
        'city' => 'Kinshasa',
        'phone' => '243900000001',
        'opening_hours' => 'Lun–Sam 9h–17h',
        'is_active' => true,
      ]
    );

    PickupPoint::query()->updateOrCreate(
      ['name' => 'Point retrait Lubumbashi'],
      [
        'address' => 'Quartier Kenya, Avenue Mobutu',
        'city' => 'Lubumbashi',
        'phone' => '243900000002',
        'opening_hours' => 'Mar–Sam 10h–16h',
        'is_active' => true,
      ]
    );
  }
}
