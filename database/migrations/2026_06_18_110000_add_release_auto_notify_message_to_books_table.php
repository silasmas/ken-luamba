<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute le modèle de message pour l'envoi programmé des alertes sortie.
 */
return new class extends Migration
{
  /**
   * Applique la migration.
   */
  public function up(): void
  {
    Schema::table('books', function (Blueprint $table): void {
      if (! Schema::hasColumn('books', 'release_auto_notify_message_id')) {
        $table->string('release_auto_notify_message_id', 120)->nullable()->after('release_auto_notify_at');
      }

      if (! Schema::hasColumn('books', 'release_auto_notify_email_subject')) {
        $table->string('release_auto_notify_email_subject')->nullable()->after('release_auto_notify_message_id');
      }

      if (! Schema::hasColumn('books', 'release_auto_notify_email_body')) {
        $table->text('release_auto_notify_email_body')->nullable()->after('release_auto_notify_email_subject');
      }
    });
  }

  /**
   * Annule la migration.
   */
  public function down(): void
  {
    Schema::table('books', function (Blueprint $table): void {
      $columns = [
        'release_auto_notify_message_id',
        'release_auto_notify_email_subject',
        'release_auto_notify_email_body',
      ];

      foreach ($columns as $column) {
        if (Schema::hasColumn('books', $column)) {
          $table->dropColumn($column);
        }
      }
    });
  }
};
