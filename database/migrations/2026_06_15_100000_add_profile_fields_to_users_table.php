<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Ajoute les champs de profil client (photo, adresses).
   */
  public function up(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->string('avatar_path')->nullable()->after('phone');
      $table->json('profile_address')->nullable()->after('avatar_path');
      $table->json('delivery_address')->nullable()->after('profile_address');
    });
  }

  /**
   * Retire les champs de profil client.
   */
  public function down(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->dropColumn(['avatar_path', 'profile_address', 'delivery_address']);
    });
  }
};
