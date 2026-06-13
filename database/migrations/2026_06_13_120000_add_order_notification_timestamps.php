<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute des horodatages pour éviter les notifications email en double.
 */
return new class extends Migration
{
  /**
   * Applique la migration.
   */
  public function up(): void
  {
    Schema::table('orders', function (Blueprint $table): void {
      $table->timestamp('payment_reminder_sent_at')->nullable()->after('completed_at');
      $table->timestamp('admin_pending_delivery_notified_at')->nullable()->after('payment_reminder_sent_at');
    });

    Schema::table('deliveries', function (Blueprint $table): void {
      $table->timestamp('stale_assignment_notified_at')->nullable()->after('delivered_at');
    });
  }

  /**
   * Annule la migration.
   */
  public function down(): void
  {
    Schema::table('orders', function (Blueprint $table): void {
      $table->dropColumn(['payment_reminder_sent_at', 'admin_pending_delivery_notified_at']);
    });

    Schema::table('deliveries', function (Blueprint $table): void {
      $table->dropColumn('stale_assignment_notified_at');
    });
  }
};
