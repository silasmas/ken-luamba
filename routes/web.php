<?php

use App\Http\Controllers\Admin\InvitationImportRejectDownloadController;
use App\Http\Controllers\DeployController;
use App\Services\DigitalAccessService;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::get('/', [DeployController::class, 'root']);

Route::middleware([
  EncryptCookies::class,
  AddQueuedCookiesToResponse::class,
  StartSession::class,
  AuthenticateSession::class,
  ShareErrorsFromSession::class,
  PreventRequestForgery::class,
  SubstituteBindings::class,
])
  ->prefix('admin')
  ->group(function (): void {
    Route::get('/invitation-import/rejects', InvitationImportRejectDownloadController::class)
      ->middleware(Authenticate::class)
      ->name('admin.invitation-import-rejects');
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
