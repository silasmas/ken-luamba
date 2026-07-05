<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Ajoute les champs téléphone pour inscription, alertes sortie et Mobile Money.
   */
  public function up(): void
  {
    Schema::table('users', function (Blueprint $table): void {
      if (! Schema::hasColumn('users', 'secondary_phone')) {
        $table->string('secondary_phone', 20)->nullable()->after('phone');
      }
    });

    Schema::table('book_release_subscriptions', function (Blueprint $table): void {
      if (! Schema::hasColumn('book_release_subscriptions', 'phone')) {
        $table->string('phone', 20)->nullable()->after('email');
      }
    });

    Schema::table('otp_codes', function (Blueprint $table): void {
      if (! Schema::hasColumn('otp_codes', 'phone')) {
        $table->string('phone', 20)->nullable()->after('full_name');
      }
    });
  }

  /**
   * Supprime les colonnes ajoutées.
   */
  public function down(): void
  {
    Schema::table('users', function (Blueprint $table): void {
      if (Schema::hasColumn('users', 'secondary_phone')) {
        $table->dropColumn('secondary_phone');
      }
    });

    Schema::table('book_release_subscriptions', function (Blueprint $table): void {
      if (Schema::hasColumn('book_release_subscriptions', 'phone')) {
        $table->dropColumn('phone');
      }
    });

    Schema::table('otp_codes', function (Blueprint $table): void {
      if (Schema::hasColumn('otp_codes', 'phone')) {
        $table->dropColumn('phone');
      }
    });
  }
};
