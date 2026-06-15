<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute la programmation d'envoi des invitations par événement.
 */
return new class extends Migration
{
  /**
   * Applique la migration.
   */
  public function up(): void
  {
    Schema::table('events', function (Blueprint $table): void {
      if (! Schema::hasColumn('events', 'invitation_auto_send_enabled')) {
        $table->boolean('invitation_auto_send_enabled')->default(false)->after('invitation_messages');
      }

      if (! Schema::hasColumn('events', 'invitation_auto_send_at')) {
        $table->timestamp('invitation_auto_send_at')->nullable()->after('invitation_auto_send_enabled');
      }

      if (! Schema::hasColumn('events', 'invitation_auto_send_sent_at')) {
        $table->timestamp('invitation_auto_send_sent_at')->nullable()->after('invitation_auto_send_at');
      }

      if (! Schema::hasColumn('events', 'invitation_auto_send_channel')) {
        $table->string('invitation_auto_send_channel', 20)->nullable()->after('invitation_auto_send_sent_at');
      }

      if (! Schema::hasColumn('events', 'invitation_auto_send_message_id')) {
        $table->string('invitation_auto_send_message_id', 120)->nullable()->after('invitation_auto_send_channel');
      }
    });
  }

  /**
   * Annule la migration.
   */
  public function down(): void
  {
    Schema::table('events', function (Blueprint $table): void {
      $columns = [
        'invitation_auto_send_enabled',
        'invitation_auto_send_at',
        'invitation_auto_send_sent_at',
        'invitation_auto_send_channel',
        'invitation_auto_send_message_id',
      ];

      foreach ($columns as $column) {
        if (Schema::hasColumn('events', $column)) {
          $table->dropColumn($column);
        }
      }
    });
  }
};
