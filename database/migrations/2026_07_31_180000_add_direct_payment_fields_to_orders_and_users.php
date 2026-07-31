<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Ajoute les champs paiement direct (source, token public) et code livreur.
   */
  public function up(): void
  {
    Schema::table('orders', function (Blueprint $table): void {
      $table->string('source', 32)->default('shop')->after('order_number');
      $table->string('public_token', 64)->nullable()->unique()->after('source');
    });

    Schema::table('users', function (Blueprint $table): void {
      $table->string('courier_code_hash')->nullable()->after('password');
    });
  }

  /**
   * Annule les colonnes paiement direct et code livreur.
   */
  public function down(): void
  {
    Schema::table('orders', function (Blueprint $table): void {
      $table->dropColumn(['source', 'public_token']);
    });

    Schema::table('users', function (Blueprint $table): void {
      $table->dropColumn('courier_code_hash');
    });
  }
};
