<?php

use App\Enums\DiscountAppliesTo;
use App\Enums\DiscountType;
use App\Models\Author;
use App\Models\QuantityDiscount;
use App\Services\DiscountService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Ajoute l'auteur ciblé pour les remises « collection complète ».
   */
  public function up(): void
  {
    Schema::table('quantity_discounts', function (Blueprint $table): void {
      $table->foreignUuid('author_id')
        ->nullable()
        ->after('book_id')
        ->constrained('authors')
        ->nullOnDelete();
    });

    $author = Author::query()->where('slug', 'ken-luamba')->first();

    if ($author === null) {
      return;
    }

    $discountService = app(DiscountService::class);
    $requiredBooks = $discountService->requiredAuthorBookCount($author->id);

    QuantityDiscount::query()
      ->where('name', 'Pack 3 livres -10%')
      ->update([
        'name' => 'Pack complet Ken Luamba -10%',
        'applies_to' => DiscountAppliesTo::AuthorCompleteSet->value,
        'author_id' => $author->id,
        'min_quantity' => max($requiredBooks, 2),
      ]);
  }

  /**
   * Supprime la colonne auteur des remises par quantité.
   */
  public function down(): void
  {
    Schema::table('quantity_discounts', function (Blueprint $table): void {
      $table->dropConstrainedForeignId('author_id');
    });
  }
};
