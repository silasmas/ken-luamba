<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Ajoute les champs profil et rôle aux utilisateurs.
   */
  public function up(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->string('full_name')->nullable()->after('name');
      $table->string('phone')->nullable()->after('email');
      $table->string('role')->default('admin')->after('phone');
      $table->boolean('is_active')->default(true)->after('role');
    });
  }

  /**
   * Retire les champs profil et rôle des utilisateurs.
   */
  public function down(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->dropColumn(['full_name', 'phone', 'role', 'is_active']);
    });
  }
};
