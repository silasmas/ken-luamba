<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Pied de page configurable, alertes sortie et favoris utilisateurs.
   */
  public function up(): void
  {
    Schema::table('contact_settings', function (Blueprint $table): void {
      if (! Schema::hasColumn('contact_settings', 'show_sdev_credit')) {
        $table->boolean('show_sdev_credit')->default(true)->after('intro_description');
      }
      if (! Schema::hasColumn('contact_settings', 'sdev_label')) {
        $table->string('sdev_label', 80)->default('SDev')->after('show_sdev_credit');
      }
      if (! Schema::hasColumn('contact_settings', 'sdev_url')) {
        $table->string('sdev_url', 255)->default('https://silasmas.com')->after('sdev_label');
      }
    });

    if (! Schema::hasTable('book_release_subscriptions')) {
      Schema::create('book_release_subscriptions', function (Blueprint $table): void {
        $table->uuid('id')->primary();
        $table->foreignUuid('book_id')->constrained()->cascadeOnDelete();
        $table->string('email');
        $table->timestamps();

        $table->unique(['book_id', 'email']);
      });
    }

    if (! Schema::hasTable('wishlist_items')) {
      Schema::create('wishlist_items', function (Blueprint $table): void {
        $table->uuid('id')->primary();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignUuid('book_id')->constrained()->cascadeOnDelete();
        $table->timestamps();

        $table->unique(['user_id', 'book_id']);
      });
    }
  }

  /**
   * Annule les changements de schéma.
   */
  public function down(): void
  {
    Schema::dropIfExists('wishlist_items');
    Schema::dropIfExists('book_release_subscriptions');

    Schema::table('contact_settings', function (Blueprint $table): void {
      $table->dropColumn(['show_sdev_credit', 'sdev_label', 'sdev_url']);
    });
  }
};
