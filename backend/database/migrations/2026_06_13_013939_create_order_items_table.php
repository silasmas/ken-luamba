<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des lignes de commande.
   */
  public function up(): void
  {
    Schema::create('order_items', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
      $table->foreignUuid('book_format_id')->constrained('book_formats')->cascadeOnDelete();
      $table->string('book_title');
      $table->string('format_type');
      $table->unsignedInteger('quantity');
      $table->decimal('unit_price', 12, 2);
      $table->decimal('total_price', 12, 2);
      $table->foreignUuid('pricing_period_id')->nullable()->constrained('pricing_periods')->nullOnDelete();
      $table->timestamps();
    });
  }

  /**
   * Supprime la table des lignes de commande.
   */
  public function down(): void
  {
    Schema::dropIfExists('order_items');
  }
};
