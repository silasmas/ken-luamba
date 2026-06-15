<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Progression de lecture audio/EPUB par utilisateur et accès numérique.
   */
  public function up(): void
  {
    if (Schema::hasTable('digital_reading_progress')) {
      return;
    }

    Schema::create('digital_reading_progress', function (Blueprint $table): void {
      $table->uuid('id')->primary();
      $table->foreignUuid('digital_access_id')->constrained()->cascadeOnDelete();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->unsignedTinyInteger('progress_percent')->default(0);
      $table->text('epub_cfi')->nullable();
      $table->unsignedInteger('audio_position_seconds')->nullable();
      $table->unsignedInteger('audio_duration_seconds')->nullable();
      $table->timestamp('last_opened_at');
      $table->timestamps();

      $table->unique(['digital_access_id', 'user_id']);
    });
  }

  /**
   * Annule la création de la table.
   */
  public function down(): void
  {
    Schema::dropIfExists('digital_reading_progress');
  }
};
