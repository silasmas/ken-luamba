<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crée la table de progression pour les liens de partage publics.
 */
return new class extends Migration
{
  /**
   * Applique la migration.
   */
  public function up(): void
  {
    Schema::create('digital_access_share_progress', function (Blueprint $table): void {
      $table->uuid('id')->primary();
      $table->foreignUuid('digital_access_share_id')
        ->constrained('digital_access_shares')
        ->cascadeOnDelete();
      $table->unsignedTinyInteger('progress_percent')->default(0);
      $table->text('epub_cfi')->nullable();
      $table->unsignedInteger('audio_position_seconds')->nullable();
      $table->unsignedInteger('audio_duration_seconds')->nullable();
      $table->timestamp('last_opened_at')->nullable();
      $table->timestamps();

      $table->unique('digital_access_share_id');
    });
  }

  /**
   * Annule la migration.
   */
  public function down(): void
  {
    Schema::dropIfExists('digital_access_share_progress');
  }
};
