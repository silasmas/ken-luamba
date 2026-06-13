<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des remises par quantité.
   */
  public function up(): void
  {
    Schema::create('quantity_discounts', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->string('name');
      $table->unsignedInteger('min_quantity');
      $table->string('discount_type');
      $table->decimal('discount_value', 12, 2);
      $table->string('applies_to');
      $table->foreignUuid('book_id')->nullable()->constrained('books')->nullOnDelete();
      $table->boolean('stackable')->default(false);
      $table->timestamp('valid_from')->nullable();
      $table->timestamp('valid_until')->nullable();
      $table->boolean('is_active')->default(true);
      $table->timestamps();
    });
  }

  /**
   * Supprime la table des remises par quantité.
   */
  public function down(): void
  {
    Schema::dropIfExists('quantity_discounts');
  }
};
