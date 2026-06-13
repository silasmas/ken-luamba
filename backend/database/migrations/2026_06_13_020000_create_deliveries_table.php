<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des livraisons et retraits.
   */
  public function up(): void
  {
    Schema::create('deliveries', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignUuid('order_id')->unique()->constrained('orders')->cascadeOnDelete();
      $table->foreignId('courier_id')->nullable()->constrained('users')->nullOnDelete();
      $table->string('status')->default('pending');
      $table->timestamp('assigned_at')->nullable();
      $table->timestamp('delivered_at')->nullable();
      $table->text('notes')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Supprime la table des livraisons.
   */
  public function down(): void
  {
    Schema::dropIfExists('deliveries');
  }
};
