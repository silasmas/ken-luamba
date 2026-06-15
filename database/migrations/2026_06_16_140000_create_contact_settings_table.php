<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des paramètres de contact (singleton).
   */
  public function up(): void
  {
    Schema::create('contact_settings', function (Blueprint $table): void {
      $table->uuid('id')->primary();
      $table->string('phone_primary')->nullable();
      $table->string('phone_secondary')->nullable();
      $table->string('email')->nullable();
      $table->text('physical_address')->nullable();
      $table->text('intro_description')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Supprime la table des paramètres de contact.
   */
  public function down(): void
  {
    Schema::dropIfExists('contact_settings');
  }
};
