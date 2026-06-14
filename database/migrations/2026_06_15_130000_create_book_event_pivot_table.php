<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Crée la table pivot livre-événement et migre les anciennes associations.
   */
  public function up(): void
  {
    if (Schema::hasTable('book_event')) {
      return;
    }

    Schema::create('book_event', function (Blueprint $table) {
      $table->foreignUuid('event_id')->constrained('events')->cascadeOnDelete();
      $table->foreignUuid('book_id')->constrained('books')->cascadeOnDelete();
      $table->primary(['event_id', 'book_id']);
    });

    if (Schema::hasColumn('events', 'book_id')) {
      DB::table('events')
        ->whereNotNull('book_id')
        ->orderBy('id')
        ->each(function (object $event): void {
          DB::table('book_event')->insertOrIgnore([
            'event_id' => $event->id,
            'book_id' => $event->book_id,
          ]);
        });

      Schema::table('events', function (Blueprint $table) {
        $table->dropForeign(['book_id']);
        $table->dropColumn('book_id');
      });
    }
  }

  /**
   * Restaure la colonne book_id et supprime la table pivot.
   */
  public function down(): void
  {
    if (! Schema::hasTable('book_event')) {
      return;
    }

    if (! Schema::hasColumn('events', 'book_id')) {
      Schema::table('events', function (Blueprint $table) {
        $table->foreignUuid('book_id')->nullable()->after('venue_details')->constrained('books')->nullOnDelete();
      });

      $rows = DB::table('book_event')
        ->select('event_id', DB::raw('MIN(book_id) as book_id'))
        ->groupBy('event_id')
        ->get();

      foreach ($rows as $row) {
        DB::table('events')
          ->where('id', $row->event_id)
          ->update(['book_id' => $row->book_id]);
      }
    }

    Schema::dropIfExists('book_event');
  }
};
