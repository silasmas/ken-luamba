<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des preuves photo de livraison.
   */
  public function up(): void
  {
    Schema::create('delivery_proofs', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignUuid('delivery_id')->constrained('deliveries')->cascadeOnDelete();
      $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
      $table->string('photo_path');
      $table->text('comment')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Supprime la table des preuves de livraison.
   */
  public function down(): void
  {
    Schema::dropIfExists('delivery_proofs');
  }
};
