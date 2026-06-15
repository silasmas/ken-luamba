<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute l'image de quatrième de couverture pour l'aperçu feuilletable.
 */
return new class extends Migration
{
  /**
   * Exécute la migration.
   */
  public function up(): void
  {
    Schema::table('books', function (Blueprint $table): void {
      $table->string('back_cover_image')->nullable()->after('cover_image');
    });
  }

  /**
   * Annule la migration.
   */
  public function down(): void
  {
    Schema::table('books', function (Blueprint $table): void {
      $table->dropColumn('back_cover_image');
    });
  }
};
