<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des paniers.
   */
  public function up(): void
  {
    Schema::create('carts', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
      $table->string('session_id')->nullable()->unique();
      $table->timestamp('expires_at')->nullable();
      $table->timestamps();

      $table->index('user_id');
    });
  }

  /**
   * Supprime la table des paniers.
   */
  public function down(): void
  {
    Schema::dropIfExists('carts');
  }
};
