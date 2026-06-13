<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des codes OTP temporaires.
   */
  public function up(): void
  {
    Schema::create('otp_codes', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
      $table->string('email');
      $table->string('full_name')->nullable();
      $table->string('code');
      $table->string('type');
      $table->timestamp('expires_at');
      $table->timestamp('used_at')->nullable();
      $table->unsignedTinyInteger('attempts')->default(0);
      $table->timestamps();

      $table->index(['email', 'type']);
    });
  }

  /**
   * Supprime la table des codes OTP.
   */
  public function down(): void
  {
    Schema::dropIfExists('otp_codes');
  }
};
