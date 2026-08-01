<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal des emails sortants pour le compteur de quota quotidien.
 */
return new class extends Migration
{
  /**
   * Crée la table mail_send_logs.
   */
  public function up(): void
  {
    Schema::create('mail_send_logs', function (Blueprint $table): void {
      $table->id();
      $table->string('to')->nullable();
      $table->string('subject')->nullable();
      $table->string('mailer', 50)->nullable();
      $table->timestamps();

      $table->index('created_at');
    });
  }

  /**
   * Supprime la table mail_send_logs.
   */
  public function down(): void
  {
    Schema::dropIfExists('mail_send_logs');
  }
};
