<?php

namespace App\Providers;

use App\Listeners\LogOutboundMail;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    //
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    FilamentTimezone::set(config('app.timezone'));

    // Hostinger SMTP : rester sous ~10 mails/minute pour éviter le blocage.
    RateLimiter::for('hostinger-mail', function (): Limit {
      return Limit::perMinute(8);
    });

    Event::listen(MessageSent::class, LogOutboundMail::class);
  }
}
