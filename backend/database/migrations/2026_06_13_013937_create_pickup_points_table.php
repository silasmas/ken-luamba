<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des points de retrait.
   */
  public function up(): void
  {
    Schema::create('pickup_points', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->string('name');
      $table->text('address');
      $table->string('city');
      $table->string('phone')->nullable();
      $table->text('opening_hours')->nullable();
      $table->boolean('is_active')->default(true);
      $table->timestamps();
    });
  }

  /**
   * Supprime la table des points de retrait.
   */
  public function down(): void
  {
    Schema::dropIfExists('pickup_points');
  }
};
