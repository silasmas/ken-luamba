<?php

use App\Enums\InvitationDispatchChannel;
use App\Enums\InvitationDispatchStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table d'historique des envois d'invitations.
   */
  public function up(): void
  {
    Schema::create('invitation_dispatch_logs', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignUuid('invitation_id')->nullable()->constrained('invitations')->nullOnDelete();
      $table->foreignUuid('event_id')->nullable()->constrained('events')->nullOnDelete();
      $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
      $table->string('channel');
      $table->string('recipient');
      $table->string('recipient_name')->nullable();
      $table->string('message_template_id')->nullable();
      $table->text('message_body');
      $table->string('status')->default(InvitationDispatchStatus::Sent->value);
      $table->text('provider_response')->nullable();
      $table->timestamp('sent_at');
      $table->timestamps();

      $table->index(['channel', 'status', 'sent_at']);
      $table->index(['event_id', 'sent_at']);
    });
  }

  /**
   * Supprime la table d'historique des envois.
   */
  public function down(): void
  {
    Schema::dropIfExists('invitation_dispatch_logs');
  }
};
