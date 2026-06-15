<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Convertit les durées de lecture (heures) en minutes pour un réglage plus fin.
 */
return new class extends Migration
{
  /**
   * Applique la migration.
   */
  public function up(): void
  {
    Schema::table('book_formats', function (Blueprint $table): void {
      if (! Schema::hasColumn('book_formats', 'digital_stream_expiry_minutes')) {
        $table->unsignedSmallInteger('digital_stream_expiry_minutes')
          ->nullable()
          ->after('digital_max_downloads');
      }

      if (! Schema::hasColumn('book_formats', 'digital_share_expiry_minutes')) {
        $table->unsignedSmallInteger('digital_share_expiry_minutes')
          ->nullable()
          ->after('digital_share_enabled');
      }
    });

    if (Schema::hasColumn('book_formats', 'digital_stream_expiry_hours')) {
      DB::table('book_formats')
        ->whereNotNull('digital_stream_expiry_hours')
        ->update([
          'digital_stream_expiry_minutes' => DB::raw('digital_stream_expiry_hours * 60'),
        ]);
    }

    if (Schema::hasColumn('book_formats', 'digital_share_expiry_hours')) {
      DB::table('book_formats')
        ->whereNotNull('digital_share_expiry_hours')
        ->update([
          'digital_share_expiry_minutes' => DB::raw('digital_share_expiry_hours * 60'),
        ]);
    }

    Schema::table('book_formats', function (Blueprint $table): void {
      if (Schema::hasColumn('book_formats', 'digital_stream_expiry_hours')) {
        $table->dropColumn('digital_stream_expiry_hours');
      }

      if (Schema::hasColumn('book_formats', 'digital_share_expiry_hours')) {
        $table->dropColumn('digital_share_expiry_hours');
      }
    });
  }

  /**
   * Annule la migration.
   */
  public function down(): void
  {
    Schema::table('book_formats', function (Blueprint $table): void {
      if (! Schema::hasColumn('book_formats', 'digital_stream_expiry_hours')) {
        $table->unsignedSmallInteger('digital_stream_expiry_hours')->nullable();
      }

      if (! Schema::hasColumn('book_formats', 'digital_share_expiry_hours')) {
        $table->unsignedSmallInteger('digital_share_expiry_hours')->nullable();
      }
    });

    if (Schema::hasColumn('book_formats', 'digital_stream_expiry_minutes')) {
      DB::table('book_formats')
        ->whereNotNull('digital_stream_expiry_minutes')
        ->update([
          'digital_stream_expiry_hours' => DB::raw('GREATEST(1, CEIL(digital_stream_expiry_minutes / 60))'),
        ]);
    }

    if (Schema::hasColumn('book_formats', 'digital_share_expiry_minutes')) {
      DB::table('book_formats')
        ->whereNotNull('digital_share_expiry_minutes')
        ->update([
          'digital_share_expiry_hours' => DB::raw('GREATEST(1, CEIL(digital_share_expiry_minutes / 60))'),
        ]);
    }

    Schema::table('book_formats', function (Blueprint $table): void {
      if (Schema::hasColumn('book_formats', 'digital_stream_expiry_minutes')) {
        $table->dropColumn('digital_stream_expiry_minutes');
      }

      if (Schema::hasColumn('book_formats', 'digital_share_expiry_minutes')) {
        $table->dropColumn('digital_share_expiry_minutes');
      }
    });
  }
};
