<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table d'audit des lectures numériques.
   */
  public function up(): void
  {
    Schema::create('digital_access_logs', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignUuid('digital_access_id')->constrained('digital_accesses')->cascadeOnDelete();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->string('action');
      $table->string('ip_address', 45)->nullable();
      $table->string('user_agent')->nullable();
      $table->timestamp('accessed_at');
      $table->timestamps();
    });
  }

  /**
   * Supprime la table des logs d'accès numériques.
   */
  public function down(): void
  {
    Schema::dropIfExists('digital_access_logs');
  }
};
