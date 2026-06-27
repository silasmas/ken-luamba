<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Ajoute la contribution volontaire au checkout et aux commandes.
   */
  public function up(): void
  {
    Schema::table('shop_settings', function (Blueprint $table): void {
      $table->boolean('extra_contribution_enabled')->default(false)->after('currency');
      $table->string('extra_contribution_label')->default('Soutien volontaire')->after('extra_contribution_enabled');
      $table->text('extra_contribution_help_text')->nullable()->after('extra_contribution_label');
    });

    Schema::table('orders', function (Blueprint $table): void {
      $table->decimal('extra_contribution_amount', 12, 2)->default(0)->after('shipping_amount');
    });
  }

  /**
   * Supprime les champs de contribution volontaire.
   */
  public function down(): void
  {
    Schema::table('orders', function (Blueprint $table): void {
      $table->dropColumn('extra_contribution_amount');
    });

    Schema::table('shop_settings', function (Blueprint $table): void {
      $table->dropColumn([
        'extra_contribution_enabled',
        'extra_contribution_label',
        'extra_contribution_help_text',
      ]);
    });
  }
};
