<?php

namespace Database\Seeders;

use App\Enums\DiscountAppliesTo;
use App\Enums\DiscountType;
use App\Models\Author;
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

    $author = Author::query()->where('slug', 'ken-luamba')->first();

    if ($author !== null) {
      $requiredBooks = app(\App\Services\DiscountService::class)->requiredAuthorBookCount($author->id);

      QuantityDiscount::query()->updateOrCreate(
        ['name' => 'Pack complet Ken Luamba -10%'],
        [
          'min_quantity' => max($requiredBooks, 2),
          'discount_type' => DiscountType::Percentage,
          'discount_value' => 10,
          'applies_to' => DiscountAppliesTo::AuthorCompleteSet,
          'author_id' => $author->id,
          'stackable' => false,
          'is_active' => true,
        ]
      );
    }
  }
}
