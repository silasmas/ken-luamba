<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table des liens de partage temporaires pour contenus numériques.
 */
return new class extends Migration
{
  /**
   * Applique la migration.
   */
  public function up(): void
  {
    Schema::create('digital_access_shares', function (Blueprint $table): void {
      $table->uuid('id')->primary();
      $table->foreignUuid('digital_access_id')->constrained('digital_accesses')->cascadeOnDelete();
      $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
      $table->string('token', 64)->unique();
      $table->string('label', 120)->nullable();
      $table->timestamp('expires_at');
      $table->timestamp('revoked_at')->nullable();
      $table->timestamps();

      $table->index(['digital_access_id', 'expires_at']);
    });
  }

  /**
   * Annule la migration.
   */
  public function down(): void
  {
    Schema::dropIfExists('digital_access_shares');
  }
};
