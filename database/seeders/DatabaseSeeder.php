<?php

namespace Database\Seeders;

use App\Enums\DiscountAppliesTo;
use App\Enums\DiscountType;
use App\Models\QuantityDiscount;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
  /**
   * Alimente la base avec les données initiales du projet.
   */
  public function run(): void
  {
    $this->call(AuthorSeeder::class);
    $this->call(AdminUserSeeder::class);
    $this->call(CourierUserSeeder::class);
    $this->call(PickupPointSeeder::class);
    $this->call(ShippingSettingSeeder::class);
    $this->call(CatalogBookSeeder::class);
    $this->call(BookReviewSeeder::class);

    QuantityDiscount::query()->updateOrCreate(
      ['name' => 'Pack 3 livres -10%'],
      [
        'min_quantity' => 3,
        'discount_type' => DiscountType::Percentage,
        'discount_value' => 10,
        'applies_to' => DiscountAppliesTo::PhysicalOnly,
        'stackable' => false,
        'is_active' => true,
      ]
    );
  }
}
