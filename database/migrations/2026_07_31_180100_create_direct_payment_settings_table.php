<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des paramètres du module paiement direct (singleton).
   */
  public function up(): void
  {
    Schema::create('direct_payment_settings', function (Blueprint $table): void {
      $table->uuid('id')->primary();
      $table->boolean('is_enabled')->default(true);
      $table->string('title')->default('Paiement direct');
      $table->text('message')->nullable();
      $table->json('pack_book_format_ids')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Supprime la table des paramètres paiement direct.
   */
  public function down(): void
  {
    Schema::dropIfExists('direct_payment_settings');
  }
};
