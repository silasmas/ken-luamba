<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute le suivi du temps de lecture effectif (et non du temps d'ouverture).
 */
return new class extends Migration
{
  /**
   * Applique la migration.
   */
  public function up(): void
  {
    Schema::table('digital_access_shares', function (Blueprint $table): void {
      if (! Schema::hasColumn('digital_access_shares', 'reading_seconds_budget')) {
        $table->unsignedInteger('reading_seconds_budget')->nullable()->after('reading_expires_at');
      }

      if (! Schema::hasColumn('digital_access_shares', 'reading_seconds_consumed')) {
        $table->unsignedInteger('reading_seconds_consumed')->default(0)->after('reading_seconds_budget');
      }

      if (! Schema::hasColumn('digital_access_shares', 'reading_timer_active_at')) {
        $table->timestamp('reading_timer_active_at')->nullable()->after('reading_seconds_consumed');
      }
    });

    DB::table('digital_access_shares')
      ->whereNotNull('first_opened_at')
      ->whereNotNull('reading_expires_at')
      ->whereNull('reading_seconds_budget')
      ->orderBy('id')
      ->chunkById(100, function ($shares): void {
        foreach ($shares as $share) {
          $openedAt = strtotime((string) $share->first_opened_at);
          $expiresAt = strtotime((string) $share->reading_expires_at);

          if ($openedAt === false || $expiresAt === false) {
            continue;
          }

          $budget = max(1, $expiresAt - $openedAt);
          $remaining = max(0, $expiresAt - time());
          $consumed = min($budget, $budget - $remaining);

          DB::table('digital_access_shares')
            ->where('id', $share->id)
            ->update([
              'reading_seconds_budget' => $budget,
              'reading_seconds_consumed' => $consumed,
              'reading_timer_active_at' => null,
            ]);
        }
      });
  }

  /**
   * Annule la migration.
   */
  public function down(): void
  {
    Schema::table('digital_access_shares', function (Blueprint $table): void {
      if (Schema::hasColumn('digital_access_shares', 'reading_timer_active_at')) {
        $table->dropColumn('reading_timer_active_at');
      }

      if (Schema::hasColumn('digital_access_shares', 'reading_seconds_consumed')) {
        $table->dropColumn('reading_seconds_consumed');
      }

      if (Schema::hasColumn('digital_access_shares', 'reading_seconds_budget')) {
        $table->dropColumn('reading_seconds_budget');
      }
    });
  }
};
