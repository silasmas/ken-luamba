<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des zones de livraison nationales.
   */
  public function up(): void
  {
    Schema::create('shipping_zones', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->string('name');
      $table->decimal('amount', 12, 2);
      $table->string('currency', 3)->default('CDF');
      $table->unsignedInteger('sort_order')->default(0);
      $table->boolean('is_active')->default(true);
      $table->timestamps();
    });
  }

  /**
   * Supprime la table des zones de livraison.
   */
  public function down(): void
  {
    Schema::dropIfExists('shipping_zones');
  }
};
