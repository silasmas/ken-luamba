<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des commandes.
   */
  public function up(): void
  {
    Schema::create('orders', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->string('order_number')->unique();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->string('status')->default('pending_payment');
      $table->string('fulfillment_type')->nullable();
      $table->foreignUuid('pickup_point_id')->nullable()->constrained('pickup_points')->nullOnDelete();
      $table->json('shipping_address')->nullable();
      $table->decimal('subtotal', 12, 2)->default(0);
      $table->decimal('discount_amount', 12, 2)->default(0);
      $table->decimal('shipping_amount', 12, 2)->default(0);
      $table->decimal('total', 12, 2)->default(0);
      $table->string('currency', 3)->default('CDF');
      $table->text('notes')->nullable();
      $table->timestamp('paid_at')->nullable();
      $table->timestamp('completed_at')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Supprime la table des commandes.
   */
  public function down(): void
  {
    Schema::dropIfExists('orders');
  }
};
