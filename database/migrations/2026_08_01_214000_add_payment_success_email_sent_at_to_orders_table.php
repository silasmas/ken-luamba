<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Ajoute le suivi d'envoi du mail de confirmation d'achat.
   */
  public function up(): void
  {
    Schema::table('orders', function (Blueprint $table): void {
      $table->timestamp('payment_success_email_sent_at')
        ->nullable()
        ->after('payment_reminder_sent_at');
    });
  }

  /**
   * Supprime le suivi d'envoi du mail de confirmation d'achat.
   */
  public function down(): void
  {
    Schema::table('orders', function (Blueprint $table): void {
      $table->dropColumn('payment_success_email_sent_at');
    });
  }
};
