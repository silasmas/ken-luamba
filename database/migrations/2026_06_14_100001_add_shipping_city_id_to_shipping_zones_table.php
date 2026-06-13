<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
  /**
   * Rattache chaque zone à une ville et migre les données existantes.
   */
  public function up(): void
  {
    Schema::table('shipping_zones', function (Blueprint $table) {
      $table->foreignUuid('shipping_city_id')
        ->nullable()
        ->after('id')
        ->constrained('shipping_cities')
        ->nullOnDelete();
    });

    $cityNames = DB::table('shipping_zone_communes')
      ->whereNotNull('city')
      ->where('city', '!=', '')
      ->distinct()
      ->pluck('city');

    if ($cityNames->isEmpty()) {
      $cityNames = collect(['Kinshasa']);
    }

    foreach ($cityNames as $cityName) {
      $cityId = (string) Str::uuid();

      DB::table('shipping_cities')->insert([
        'id' => $cityId,
        'name' => $cityName,
        'is_delivery_available' => true,
        'sort_order' => 0,
        'created_at' => now(),
        'updated_at' => now(),
      ]);

      $zoneIds = DB::table('shipping_zone_communes')
        ->where('city', $cityName)
        ->pluck('shipping_zone_id')
        ->unique();

      if ($zoneIds->isNotEmpty()) {
        DB::table('shipping_zones')
          ->whereIn('id', $zoneIds)
          ->update(['shipping_city_id' => $cityId]);
      }
    }

    $fallbackCityId = DB::table('shipping_cities')->orderBy('name')->value('id');

    if ($fallbackCityId !== null) {
      DB::table('shipping_zones')
        ->whereNull('shipping_city_id')
        ->update(['shipping_city_id' => $fallbackCityId]);
    }
  }

  /**
   * Retire le lien ville des zones.
   */
  public function down(): void
  {
    Schema::table('shipping_zones', function (Blueprint $table) {
      $table->dropConstrainedForeignId('shipping_city_id');
    });
  }
};
