<?php

use App\Http\Controllers\Api\V1\AppearanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AuthorController;
use App\Http\Controllers\Api\V1\BookReleaseNotificationController;
use App\Http\Controllers\Api\V1\BookController;
use App\Http\Controllers\Api\V1\BookReviewController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\CourierController;
use App\Http\Controllers\Api\V1\InvitationController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\LibraryController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PickupPointController;
use App\Http\Controllers\Api\V1\WishlistController;
use App\Http\Controllers\Api\V1\ShippingController;
use App\Http\Controllers\Api\V1\ShopConfigController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
  Route::get('/health', HealthController::class);
  Route::get('/appearance', AppearanceController::class);
  Route::get('/contact', ContactController::class);
  Route::get('/shop/config', ShopConfigController::class);

  Route::get('/authors/{slug}', [AuthorController::class, 'show']);
  Route::get('/books', [BookController::class, 'index']);
  Route::get('/books/{slug}/reviews', [BookReviewController::class, 'index']);
  Route::get('/books/{slug}', [BookController::class, 'show']);
  Route::post('/books/{slug}/release-notifications', [BookReleaseNotificationController::class, 'store']);
  Route::get('/pickup-points', [PickupPointController::class, 'index']);
  Route::get('/shipping/config', [ShippingController::class, 'config']);
  Route::post('/shipping/quote', [ShippingController::class, 'quote']);
  Route::get('/payments/mobile-providers', [PaymentController::class, 'mobileProviders']);
  Route::get('/invitations/{token}', [InvitationController::class, 'show']);
  Route::post('/invitations/{token}/rsvp', [InvitationController::class, 'respond']);

  Route::post('/payments/flexpay-callback', [PaymentController::class, 'flexpayCallback']);
  Route::get('/payments/status', [PaymentController::class, 'checkStatus']);
  Route::get('/payments/card-return', [PaymentController::class, 'cardReturn']);

  Route::prefix('cart')->middleware('sanctum.optional')->group(function (): void {
    Route::post('/session', [CartController::class, 'createSession']);
    Route::get('/', [CartController::class, 'show']);
    Route::post('/items', [CartController::class, 'storeItem']);
    Route::patch('/items/{itemId}', [CartController::class, 'updateItem']);
    Route::delete('/items/{itemId}', [CartController::class, 'destroyItem']);
  });

  Route::prefix('auth')->group(function (): void {
    $otpThrottle = app()->environment('local') ? 'throttle:120,1' : 'throttle:30,1';

    Route::post('/register', [AuthController::class, 'register'])->middleware($otpThrottle);
    Route::post('/login', [AuthController::class, 'login'])->middleware($otpThrottle);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:60,1');

    Route::middleware('auth:sanctum')->group(function (): void {
      Route::post('/logout', [AuthController::class, 'logout']);
      Route::get('/me', [AuthController::class, 'me']);
      Route::patch('/me', [AuthController::class, 'updateProfile']);
      Route::post('/me/avatar', [AuthController::class, 'updateAvatar']);
    });
  });

  Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{orderNumber}', [OrderController::class, 'show']);
    Route::post('/orders/{orderNumber}/pay', [PaymentController::class, 'initiate']);
    Route::post('/orders/{orderNumber}/confirm-receipt', [OrderController::class, 'confirmReceipt']);
    Route::post('/orders/{orderNumber}/dispute-delivery', [OrderController::class, 'disputeDelivery']);

    Route::get('/library', [LibraryController::class, 'index']);
    Route::get('/library/{accessId}/stream', [LibraryController::class, 'stream']);
    Route::get('/library/{accessId}/file', [LibraryController::class, 'file']);
    Route::put('/library/{accessId}/progress', [LibraryController::class, 'saveProgress']);

    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist/{bookId}/toggle', [WishlistController::class, 'toggle']);

    Route::post('/books/{slug}/reviews', [BookReviewController::class, 'store']);

    Route::prefix('courier')->group(function (): void {
      Route::get('/deliveries', [CourierController::class, 'deliveries']);
      Route::post('/deliveries/{deliveryId}/accept', [CourierController::class, 'accept']);
      Route::post('/scan', [CourierController::class, 'scan']);
      Route::post('/confirm', [CourierController::class, 'confirm']);
    });
  });
});
