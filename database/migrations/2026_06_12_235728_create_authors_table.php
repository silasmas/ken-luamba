<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des auteurs (profils publics).
   */
  public function up(): void
  {
    Schema::create('authors', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->string('full_name');
      $table->string('slug')->unique();
      $table->string('title')->nullable();
      $table->text('short_bio')->nullable();
      $table->longText('full_bio')->nullable();
      $table->string('profile_image')->nullable();
      $table->string('cover_image')->nullable();
      $table->json('social_links')->nullable();
      $table->text('featured_quote')->nullable();
      $table->boolean('is_primary')->default(false);
      $table->boolean('is_published')->default(false);
      $table->string('meta_title')->nullable();
      $table->text('meta_description')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Supprime la table des auteurs.
   */
  public function down(): void
  {
    Schema::dropIfExists('authors');
  }
};
