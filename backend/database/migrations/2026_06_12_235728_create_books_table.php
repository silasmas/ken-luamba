<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des livres liés à un auteur.
   */
  public function up(): void
  {
    Schema::create('books', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignUuid('author_id')->constrained('authors')->cascadeOnDelete();
      $table->string('title');
      $table->string('slug')->unique();
      $table->text('description')->nullable();
      $table->text('author_note')->nullable();
      $table->string('cover_image')->nullable();
      $table->boolean('is_published')->default(false);
      $table->boolean('is_featured')->default(false);
      $table->unsignedInteger('sort_order')->default(0);
      $table->timestamp('published_at')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Supprime la table des livres.
   */
  public function down(): void
  {
    Schema::dropIfExists('books');
  }
};
