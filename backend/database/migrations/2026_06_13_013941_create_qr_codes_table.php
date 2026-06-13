<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des QR codes de commande.
   */
  public function up(): void
  {
    Schema::create('qr_codes', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignUuid('order_id')->unique()->constrained('orders')->cascadeOnDelete();
      $table->string('token')->unique();
      $table->boolean('is_used')->default(false);
      $table->timestamp('used_at')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Supprime la table des QR codes.
   */
  public function down(): void
  {
    Schema::dropIfExists('qr_codes');
  }
};
