<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des villes couvertes par la politique nationale.
   */
  public function up(): void
  {
    Schema::create('shipping_cities', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->string('name')->unique();
      $table->boolean('is_delivery_available')->default(false);
      $table->unsignedInteger('sort_order')->default(0);
      $table->timestamps();
    });
  }

  /**
   * Supprime la table des villes de livraison.
   */
  public function down(): void
  {
    Schema::dropIfExists('shipping_cities');
  }
};
