<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Messages d'alerte sortie, envoi programmé et journal des envois.
   */
  public function up(): void
  {
    Schema::table('books', function (Blueprint $table): void {
      if (! Schema::hasColumn('books', 'release_notification_messages')) {
        $table->json('release_notification_messages')->nullable()->after('preorder_campaign_bonuses');
      }
      if (! Schema::hasColumn('books', 'release_auto_notify_enabled')) {
        $table->boolean('release_auto_notify_enabled')->default(false)->after('release_notification_messages');
      }
      if (! Schema::hasColumn('books', 'release_auto_notify_at')) {
        $table->timestamp('release_auto_notify_at')->nullable()->after('release_auto_notify_enabled');
      }
      if (! Schema::hasColumn('books', 'release_auto_notify_sent_at')) {
        $table->timestamp('release_auto_notify_sent_at')->nullable()->after('release_auto_notify_at');
      }
    });

    Schema::table('book_release_subscriptions', function (Blueprint $table): void {
      if (! Schema::hasColumn('book_release_subscriptions', 'notified_at')) {
        $table->timestamp('notified_at')->nullable()->after('email');
      }
    });

    if (! Schema::hasTable('book_release_dispatch_logs')) {
      Schema::create('book_release_dispatch_logs', function (Blueprint $table): void {
        $table->uuid('id')->primary();
        $table->foreignUuid('book_id')->constrained()->cascadeOnDelete();
        $table->foreignUuid('book_release_subscription_id')->nullable()->constrained()->nullOnDelete();
        $table->string('recipient_email');
        $table->string('message_id')->nullable();
        $table->string('subject');
        $table->text('body');
        $table->string('status', 20);
        $table->timestamp('scheduled_for')->nullable();
        $table->timestamp('sent_at')->nullable();
        $table->text('error_message')->nullable();
        $table->timestamps();
      });
    }
  }

  /**
   * Annule les changements de schéma.
   */
  public function down(): void
  {
    Schema::dropIfExists('book_release_dispatch_logs');

    Schema::table('book_release_subscriptions', function (Blueprint $table): void {
      $table->dropColumn('notified_at');
    });

    Schema::table('books', function (Blueprint $table): void {
      $table->dropColumn([
        'release_notification_messages',
        'release_auto_notify_enabled',
        'release_auto_notify_at',
        'release_auto_notify_sent_at',
      ]);
    });
  }
};
