<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute les emplacements photos auteur pour l'accueil et la page dédiée.
 */
return new class extends Migration
{
  /**
   * Exécute la migration.
   */
  public function up(): void
  {
    Schema::table('authors', function (Blueprint $table): void {
      $table->string('home_hero_primary_image')->nullable()->after('cover_image');
      $table->string('home_hero_overlay_image')->nullable()->after('home_hero_primary_image');
      $table->string('home_section_primary_image')->nullable()->after('home_hero_overlay_image');
      $table->string('home_section_overlay_image')->nullable()->after('home_section_primary_image');
      $table->string('page_primary_image')->nullable()->after('home_section_overlay_image');
      $table->string('page_overlay_image')->nullable()->after('page_primary_image');
    });
  }

  /**
   * Annule la migration.
   */
  public function down(): void
  {
    Schema::table('authors', function (Blueprint $table): void {
      $table->dropColumn([
        'home_hero_primary_image',
        'home_hero_overlay_image',
        'home_section_primary_image',
        'home_section_overlay_image',
        'page_primary_image',
        'page_overlay_image',
      ]);
    });
  }
};
