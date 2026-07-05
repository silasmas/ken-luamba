<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Ajoute le suivi de réception article par article.
   */
  public function up(): void
  {
    Schema::table('order_items', function (Blueprint $table): void {
      if (! Schema::hasColumn('order_items', 'received_at')) {
        $table->timestamp('received_at')->nullable()->after('total_price');
      }
    });
  }

  /**
   * Supprime le suivi de réception par article.
   */
  public function down(): void
  {
    Schema::table('order_items', function (Blueprint $table): void {
      if (Schema::hasColumn('order_items', 'received_at')) {
        $table->dropColumn('received_at');
      }
    });
  }
};
