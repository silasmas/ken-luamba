<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute la durée de lecture partagée et le suivi de session par lien.
 */
return new class extends Migration
{
  /**
   * Applique la migration.
   */
  public function up(): void
  {
    Schema::table('book_formats', function (Blueprint $table): void {
      if (! Schema::hasColumn('book_formats', 'digital_share_reading_minutes')) {
        $table->unsignedSmallInteger('digital_share_reading_minutes')
          ->nullable()
          ->after('digital_share_expiry_minutes');
      }
    });

    Schema::table('digital_access_shares', function (Blueprint $table): void {
      if (! Schema::hasColumn('digital_access_shares', 'first_opened_at')) {
        $table->timestamp('first_opened_at')->nullable()->after('expires_at');
      }

      if (! Schema::hasColumn('digital_access_shares', 'reading_expires_at')) {
        $table->timestamp('reading_expires_at')->nullable()->after('first_opened_at');
      }
    });
  }

  /**
   * Annule la migration.
   */
  public function down(): void
  {
    Schema::table('digital_access_shares', function (Blueprint $table): void {
      if (Schema::hasColumn('digital_access_shares', 'reading_expires_at')) {
        $table->dropColumn('reading_expires_at');
      }

      if (Schema::hasColumn('digital_access_shares', 'first_opened_at')) {
        $table->dropColumn('first_opened_at');
      }
    });

    Schema::table('book_formats', function (Blueprint $table): void {
      if (Schema::hasColumn('book_formats', 'digital_share_reading_minutes')) {
        $table->dropColumn('digital_share_reading_minutes');
      }
    });
  }
};
