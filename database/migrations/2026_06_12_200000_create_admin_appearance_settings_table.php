<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table des paramètres d'apparence de l'administration.
   */
  public function up(): void
  {
    Schema::create('admin_appearance_settings', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->string('site_title')->default('Ken Luamba');
      $table->string('logo_path')->nullable();
      $table->string('favicon_path')->nullable();
      $table->string('color_primary', 7)->default('#2563eb');
      $table->string('color_button_text', 7)->default('#ffffff');
      $table->string('color_body_text', 7)->default('#0f172a');
      $table->string('color_menu_active', 7)->default('#2563eb');
      $table->string('color_menu_active_text', 7)->default('#ffffff');
      $table->string('color_input_focus', 7)->default('#2563eb');
      $table->boolean('sidebar_collapsible')->default(true);
      $table->unsignedInteger('sms_manual_balance')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Supprime la table des paramètres d'apparence.
   */
  public function down(): void
  {
    Schema::dropIfExists('admin_appearance_settings');
  }
};
