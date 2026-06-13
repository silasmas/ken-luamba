<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des communes rattachées à une zone.
   */
  public function up(): void
  {
    Schema::create('shipping_zone_communes', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignUuid('shipping_zone_id')->constrained('shipping_zones')->cascadeOnDelete();
      $table->string('name');
      $table->string('city')->nullable();
      $table->timestamps();

      $table->unique(['shipping_zone_id', 'name', 'city']);
    });
  }

  /**
   * Supprime la table des communes de zone.
   */
  public function down(): void
  {
    Schema::dropIfExists('shipping_zone_communes');
  }
};
