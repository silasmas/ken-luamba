<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Supprime les associations livre-événement en double dans la table pivot.
   */
  public function up(): void
  {
    if (! Schema::hasTable('book_event')) {
      return;
    }

    $uniquePairs = DB::table('book_event')
      ->select('event_id', 'book_id')
      ->groupBy('event_id', 'book_id')
      ->get();

    DB::table('book_event')->delete();

    foreach ($uniquePairs as $pair) {
      DB::table('book_event')->insert([
        'event_id' => $pair->event_id,
        'book_id' => $pair->book_id,
      ]);
    }
  }

  /**
   * Aucune restauration : opération de nettoyage uniquement.
   */
  public function down(): void
  {
    //
  }
};
