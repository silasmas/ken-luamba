<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Ajoute les avantages affichés dans la campagne de lancement.
   */
  public function up(): void
  {
    Schema::table('books', function (Blueprint $table) {
      $table->json('preorder_campaign_bonuses')->nullable()->after('preorder_reserved_count');
    });
  }

  /**
   * Supprime les avantages de campagne.
   */
  public function down(): void
  {
    Schema::table('books', function (Blueprint $table) {
      $table->dropColumn('preorder_campaign_bonuses');
    });
  }
};
