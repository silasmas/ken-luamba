<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
      $middleware->alias([
        'sanctum.optional' => \App\Http\Middleware\OptionalSanctumAuth::class,
      ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
      $schedule->command('orders:send-payment-reminders')->hourly();
      $schedule->command('deliveries:send-stale-alerts')->hourly();
      $schedule->command('release-notifications:dispatch-scheduled')->everyMinute();
      $schedule->command('invitations:dispatch-scheduled')->everyMinute();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (MethodNotAllowedHttpException $exception, Request $request) {
            if (
                $request->isMethod('GET')
                && preg_match('#^livewire-[a-f0-9]+/update$#', $request->path()) === 1
            ) {
                return redirect()
                    ->to('/admin')
                    ->with('status', 'Session admin rafraîchie. Reprenez votre action depuis le menu.');
            }

            return null;
        });
    })->create();
