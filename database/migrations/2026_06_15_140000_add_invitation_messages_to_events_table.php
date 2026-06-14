<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Ajoute les modèles de messages d'invitation sur les événements.
   */
  public function up(): void
  {
    Schema::table('events', function (Blueprint $table) {
      $table->json('invitation_messages')->nullable()->after('welcome_message');
    });
  }

  /**
   * Supprime les modèles de messages d'invitation.
   */
  public function down(): void
  {
    Schema::table('events', function (Blueprint $table) {
      $table->dropColumn('invitation_messages');
    });
  }
};
