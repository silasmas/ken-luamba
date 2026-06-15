<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les limites numériques configurables par format (EPUB, PDF, MP3).
 */
return new class extends Migration
{
  /**
   * Applique la migration.
   */
  public function up(): void
  {
    Schema::table('book_formats', function (Blueprint $table): void {
      if (! Schema::hasColumn('book_formats', 'digital_max_downloads')) {
        $table->unsignedSmallInteger('digital_max_downloads')
          ->nullable()
          ->after('digital_file_type');
      }

      if (! Schema::hasColumn('book_formats', 'digital_stream_expiry_hours')) {
        $table->unsignedSmallInteger('digital_stream_expiry_hours')
          ->nullable()
          ->after('digital_max_downloads');
      }
    });
  }

  /**
   * Annule la migration.
   */
  public function down(): void
  {
    Schema::table('book_formats', function (Blueprint $table): void {
      if (Schema::hasColumn('book_formats', 'digital_stream_expiry_hours')) {
        $table->dropColumn('digital_stream_expiry_hours');
      }

      if (Schema::hasColumn('book_formats', 'digital_max_downloads')) {
        $table->dropColumn('digital_max_downloads');
      }
    });
  }
};
