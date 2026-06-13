<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des périodes tarifaires par format.
   */
  public function up(): void
  {
    Schema::create('pricing_periods', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignUuid('book_format_id')->constrained('book_formats')->cascadeOnDelete();
      $table->string('label');
      $table->string('type');
      $table->decimal('price', 12, 2);
      $table->string('currency', 3)->default('CDF');
      $table->timestamp('start_at');
      $table->timestamp('end_at');
      $table->boolean('is_active')->default(true);
      $table->timestamps();
    });
  }

  /**
   * Supprime la table des périodes tarifaires.
   */
  public function down(): void
  {
    Schema::dropIfExists('pricing_periods');
  }
};
