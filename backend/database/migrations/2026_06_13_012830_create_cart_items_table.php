<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des lignes de panier.
   */
  public function up(): void
  {
    Schema::create('cart_items', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignUuid('cart_id')->constrained('carts')->cascadeOnDelete();
      $table->foreignUuid('book_format_id')->constrained('book_formats')->cascadeOnDelete();
      $table->unsignedInteger('quantity')->default(1);
      $table->decimal('unit_price', 12, 2);
      $table->foreignUuid('pricing_period_id')->nullable()->constrained('pricing_periods')->nullOnDelete();
      $table->timestamps();

      $table->unique(['cart_id', 'book_format_id']);
    });
  }

  /**
   * Supprime la table des lignes de panier.
   */
  public function down(): void
  {
    Schema::dropIfExists('cart_items');
  }
};
