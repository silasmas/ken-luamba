<?php

use App\Http\Controllers\DeployController;
use App\Services\DigitalAccessService;
use Illuminate\Support\Facades\Route;

Route::get('/', [DeployController::class, 'root']);

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
