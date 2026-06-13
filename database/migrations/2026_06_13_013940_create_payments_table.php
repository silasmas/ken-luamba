<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des paiements.
   */
  public function up(): void
  {
    Schema::create('payments', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
      $table->string('provider')->default('flexpay');
      $table->string('provider_reference')->nullable();
      $table->decimal('amount', 12, 2);
      $table->string('currency', 3)->default('CDF');
      $table->string('status')->default('pending');
      $table->string('channel')->nullable();
      $table->string('phone')->nullable();
      $table->json('metadata')->nullable();
      $table->timestamp('paid_at')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Supprime la table des paiements.
   */
  public function down(): void
  {
    Schema::dropIfExists('payments');
  }
};
