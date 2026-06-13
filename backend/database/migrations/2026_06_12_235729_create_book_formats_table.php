<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des formats disponibles par livre.
   */
  public function up(): void
  {
    Schema::create('book_formats', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignUuid('book_id')->constrained('books')->cascadeOnDelete();
      $table->string('type');
      $table->string('sku')->unique();
      $table->unsignedInteger('stock_quantity')->nullable();
      $table->string('digital_file_path')->nullable();
      $table->boolean('is_active')->default(true);
      $table->timestamps();

      $table->unique(['book_id', 'type']);
    });
  }

  /**
   * Supprime la table des formats de livre.
   */
  public function down(): void
  {
    Schema::dropIfExists('book_formats');
  }
};
