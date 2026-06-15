<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les titres/fonctions multiples de l'auteur (comme des tags).
 */
return new class extends Migration
{
  /**
   * Exécute la migration.
   */
  public function up(): void
  {
    Schema::table('authors', function (Blueprint $table): void {
      $table->json('roles')->nullable()->after('title');
    });
  }

  /**
   * Annule la migration.
   */
  public function down(): void
  {
    Schema::table('authors', function (Blueprint $table): void {
      $table->dropColumn('roles');
    });
  }
};
