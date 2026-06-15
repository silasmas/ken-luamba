<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute le PDF d'extrait public feuilletable par livre.
 */
return new class extends Migration
{
  /**
   * Exécute la migration.
   */
  public function up(): void
  {
    Schema::table('books', function (Blueprint $table): void {
      $table->string('preview_pdf_path')->nullable()->after('cover_image');
    });
  }

  /**
   * Annule la migration.
   */
  public function down(): void
  {
    Schema::table('books', function (Blueprint $table): void {
      $table->dropColumn('preview_pdf_path');
    });
  }
};
