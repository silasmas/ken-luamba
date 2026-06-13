<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Ajoute les métadonnées éditoriales pour la fiche livre.
   */
  public function up(): void
  {
    Schema::table('books', function (Blueprint $table) {
      $table->string('subtitle')->nullable()->after('title');
      $table->string('tagline')->nullable()->after('subtitle');
      $table->string('category')->nullable()->after('tagline');
      $table->unsignedSmallInteger('page_count')->nullable()->after('category');
      $table->unsignedSmallInteger('reading_time_minutes')->nullable()->after('page_count');
      $table->string('language')->default('Français')->after('reading_time_minutes');
      $table->date('release_date')->nullable()->after('language');
      $table->json('themes')->nullable()->after('release_date');
      $table->json('about_paragraphs')->nullable()->after('themes');
      $table->json('excerpt')->nullable()->after('about_paragraphs');
      $table->string('accent_color', 7)->default('#1b1f2a')->after('excerpt');
      $table->unsignedInteger('preorder_campaign_goal')->nullable()->after('accent_color');
      $table->unsignedInteger('preorder_reserved_count')->default(0)->after('preorder_campaign_goal');
    });
  }

  /**
   * Supprime les métadonnées éditoriales.
   */
  public function down(): void
  {
    Schema::table('books', function (Blueprint $table) {
      $table->dropColumn([
        'subtitle',
        'tagline',
        'category',
        'page_count',
        'reading_time_minutes',
        'language',
        'release_date',
        'themes',
        'about_paragraphs',
        'excerpt',
        'accent_color',
        'preorder_campaign_goal',
        'preorder_reserved_count',
      ]);
    });
  }
};
