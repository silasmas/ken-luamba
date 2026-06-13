<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des accès aux contenus numériques.
   */
  public function up(): void
  {
    Schema::create('digital_accesses', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
      $table->foreignUuid('order_item_id')->constrained('order_items')->cascadeOnDelete();
      $table->foreignUuid('book_format_id')->constrained('book_formats')->cascadeOnDelete();
      $table->boolean('is_active')->default(true);
      $table->timestamp('granted_at');
      $table->timestamp('expires_at')->nullable();
      $table->timestamps();

      $table->unique(['user_id', 'order_item_id']);
    });
  }

  /**
   * Supprime la table des accès numériques.
   */
  public function down(): void
  {
    Schema::dropIfExists('digital_accesses');
  }
};
