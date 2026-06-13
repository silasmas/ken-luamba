<?php

namespace Database\Seeders;

use App\Enums\BookFormatType;
use App\Enums\DiscountAppliesTo;
use App\Enums\DiscountType;
use App\Enums\PricingPeriodType;
use App\Models\Author;
use App\Models\Book;
use App\Models\BookFormat;
use App\Models\PricingPeriod;
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

    $author = Author::query()->where('slug', 'ken-luamba')->first();

    if ($author === null) {
      return;
    }

    $book = Book::query()->updateOrCreate(
      ['slug' => 'mon-premier-ouvrage'],
      [
        'author_id' => $author->id,
        'title' => 'Mon Premier Ouvrage',
        'description' => 'Un ouvrage de démonstration pour valider le catalogue Ken Luamba.',
        'author_note' => 'Ce livre est né d\'une profonde conviction : partager la lumière avec le plus grand nombre.',
        'is_published' => true,
        'is_featured' => true,
        'sort_order' => 1,
        'published_at' => now(),
      ]
    );

    $hardcover = BookFormat::query()->updateOrCreate(
      ['sku' => 'KL-MPO-HC'],
      [
        'book_id' => $book->id,
        'type' => BookFormatType::Hardcover,
        'stock_quantity' => 100,
        'is_active' => true,
      ]
    );

    $ebook = BookFormat::query()->updateOrCreate(
      ['sku' => 'KL-MPO-EB'],
      [
        'book_id' => $book->id,
        'type' => BookFormatType::Ebook,
        'stock_quantity' => null,
        'is_active' => true,
      ]
    );

    PricingPeriod::query()->updateOrCreate(
      [
        'book_format_id' => $hardcover->id,
        'label' => 'Pré-commande lancement',
      ],
      [
        'type' => PricingPeriodType::Preorder,
        'price' => 25000,
        'currency' => 'CDF',
        'start_at' => now()->subDays(7),
        'end_at' => now()->addMonths(2),
        'is_active' => true,
      ]
    );

    PricingPeriod::query()->updateOrCreate(
      [
        'book_format_id' => $ebook->id,
        'label' => 'Prix ebook lancement',
      ],
      [
        'type' => PricingPeriodType::Regular,
        'price' => 5000,
        'currency' => 'CDF',
        'start_at' => now()->subDays(7),
        'end_at' => now()->addMonths(2),
        'is_active' => true,
      ]
    );

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
