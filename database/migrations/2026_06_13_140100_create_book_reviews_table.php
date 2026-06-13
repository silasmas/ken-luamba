<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des témoignages lecteurs modérés par l'admin.
   */
  public function up(): void
  {
    Schema::create('book_reviews', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignUuid('book_id')->constrained('books')->cascadeOnDelete();
      $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
      $table->string('author_role')->nullable();
      $table->unsignedTinyInteger('rating');
      $table->text('content');
      $table->string('status')->default('pending');
      $table->timestamp('moderated_at')->nullable();
      $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
      $table->timestamps();

      $table->unique(['book_id', 'user_id']);
    });
  }

  /**
   * Supprime la table des témoignages.
   */
  public function down(): void
  {
    Schema::dropIfExists('book_reviews');
  }
};
