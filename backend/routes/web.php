<?php

use App\Services\DigitalAccessService;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/**
 * Route signée pour le streaming de contenus numériques.
 */
Route::get('/digital/stream/{accessId}/{userId}', function (
  string $accessId,
  int $userId,
  DigitalAccessService $digitalAccessService,
) {
  return $digitalAccessService->streamFile($accessId, $userId);
})->middleware('signed')->name('digital.stream');
