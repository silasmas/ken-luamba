<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des paramètres boutique (devise, singleton).
   */
  public function up(): void
  {
    Schema::create('shop_settings', function (Blueprint $table): void {
      $table->uuid('id')->primary();
      $table->string('currency', 3)->default('CDF');
      $table->timestamps();
    });
  }

  /**
   * Supprime la table des paramètres boutique.
   */
  public function down(): void
  {
    Schema::dropIfExists('shop_settings');
  }
};
