<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute le type de fichier numérique et le compteur de téléchargements.
 */
return new class extends Migration
{
  /**
   * Applique la migration.
   */
  public function up(): void
  {
    Schema::table('book_formats', function (Blueprint $table): void {
      $table->string('digital_file_type', 20)->nullable()->after('digital_file_path');
    });

    Schema::table('digital_accesses', function (Blueprint $table): void {
      $table->unsignedSmallInteger('download_count')->default(0)->after('expires_at');
    });
  }

  /**
   * Annule la migration.
   */
  public function down(): void
  {
    Schema::table('book_formats', function (Blueprint $table): void {
      $table->dropColumn('digital_file_type');
    });

    Schema::table('digital_accesses', function (Blueprint $table): void {
      $table->dropColumn('download_count');
    });
  }
};
