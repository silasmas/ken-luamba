<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des paramètres globaux de livraison.
   */
  public function up(): void
  {
    Schema::create('shipping_settings', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->string('pricing_mode')->default('fixed');
      $table->decimal('fixed_amount', 12, 2)->default(5000);
      $table->string('currency', 3)->default('CDF');
      $table->string('domestic_country_code', 2)->default('CD');
      $table->string('domestic_country_name')->default('République Démocratique du Congo');
      $table->string('international_policy')->default('quote');
      $table->decimal('international_amount', 12, 2)->nullable();
      $table->text('international_message')->nullable();
      $table->boolean('is_active')->default(true);
      $table->timestamps();
    });
  }

  /**
   * Supprime la table des paramètres de livraison.
   */
  public function down(): void
  {
    Schema::dropIfExists('shipping_settings');
  }
};
