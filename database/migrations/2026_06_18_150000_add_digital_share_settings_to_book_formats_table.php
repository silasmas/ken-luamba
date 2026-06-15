<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les réglages de partage par lien pour les formats numériques.
 */
return new class extends Migration
{
  /**
   * Applique la migration.
   */
  public function up(): void
  {
    Schema::table('book_formats', function (Blueprint $table): void {
      if (! Schema::hasColumn('book_formats', 'digital_share_enabled')) {
        $table->boolean('digital_share_enabled')
          ->default(false)
          ->after('digital_stream_expiry_hours');
      }

      if (! Schema::hasColumn('book_formats', 'digital_share_expiry_hours')) {
        $table->unsignedSmallInteger('digital_share_expiry_hours')
          ->nullable()
          ->after('digital_share_enabled');
      }

      if (! Schema::hasColumn('book_formats', 'digital_share_max_links')) {
        $table->unsignedSmallInteger('digital_share_max_links')
          ->nullable()
          ->after('digital_share_expiry_hours');
      }
    });
  }

  /**
   * Annule la migration.
   */
  public function down(): void
  {
    Schema::table('book_formats', function (Blueprint $table): void {
      if (Schema::hasColumn('book_formats', 'digital_share_max_links')) {
        $table->dropColumn('digital_share_max_links');
      }

      if (Schema::hasColumn('book_formats', 'digital_share_expiry_hours')) {
        $table->dropColumn('digital_share_expiry_hours');
      }

      if (Schema::hasColumn('book_formats', 'digital_share_enabled')) {
        $table->dropColumn('digital_share_enabled');
      }
    });
  }
};
