<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée les tables événements et invitations.
   */
  public function up(): void
  {
    Schema::create('events', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->string('title');
      $table->string('slug')->unique();
      $table->string('type')->default('other');
      $table->text('description')->nullable();
      $table->text('welcome_message')->nullable();
      $table->timestamp('starts_at');
      $table->timestamp('ends_at')->nullable();
      $table->string('location')->nullable();
      $table->text('venue_details')->nullable();
      $table->boolean('is_published')->default(true);
      $table->timestamps();
    });

    Schema::create('book_event', function (Blueprint $table) {
      $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
      $table->foreignUuid('book_id')->constrained('books')->cascadeOnDelete();
      $table->primary(['event_id', 'book_id']);
    });

    Schema::create('invitations', function (Blueprint $table) {
      $table->uuid('id')->primary();
      $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
      $table->string('full_name');
      $table->string('email')->nullable();
      $table->string('phone')->nullable();
      $table->string('organization')->nullable();
      $table->string('token', 64)->unique();
      $table->string('rsvp_status')->default('pending');
      $table->text('guest_message')->nullable();
      $table->timestamp('responded_at')->nullable();
      $table->timestamp('email_sent_at')->nullable();
      $table->timestamp('whatsapp_sent_at')->nullable();
      $table->timestamp('sms_sent_at')->nullable();
      $table->text('admin_notes')->nullable();
      $table->timestamps();

      $table->index(['event_id', 'rsvp_status']);
    });
  }

  /**
   * Supprime les tables événements et invitations.
   */
  public function down(): void
  {
    Schema::dropIfExists('invitations');
    Schema::dropIfExists('book_event');
    Schema::dropIfExists('events');
  }
};
