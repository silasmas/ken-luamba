<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Concerns\HasFullWidthContent;
use App\Models\AdminAppearanceSetting;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
  use HasFullWidthContent;

  /**
   * Configure le panel d'administration Filament.
   *
   * @param Panel $panel Instance panel Filament
   * @return Panel Panel configuré
   */
  public function panel(Panel $panel): Panel
  {
    return $panel
      ->default()
      ->id('admin')
      ->path('admin')
      ->login()
      ->brandName(fn (): string => AdminAppearanceSetting::instance()->site_title)
      ->brandLogo(fn (): string => AdminAppearanceSetting::instance()->logoUrl())
      ->brandLogoHeight('2.75rem')
      ->favicon(fn (): string => AdminAppearanceSetting::instance()->faviconUrl())
      ->maxContentWidth($this->maxContentWidth)
      ->colors(fn (): array => [
        'primary' => Color::hex(AdminAppearanceSetting::instance()->color_primary ?? '#2563eb'),
      ])
      ->sidebarCollapsibleOnDesktop(fn (): bool => (bool) AdminAppearanceSetting::instance()->sidebar_collapsible)
      ->plugins([
        FilamentShieldPlugin::make()
          ->navigationGroup('Sécurité et permissions'),
      ])
      ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
      ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
      ->pages([
        Dashboard::class,
      ])
      ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
      ->widgets([
        \App\Filament\Widgets\InvitationMessagingStatsWidget::class,
        \App\Filament\Widgets\CatalogStatsWidget::class,
        \App\Filament\Widgets\SalesOverviewWidget::class,
        \App\Filament\Widgets\BookFormatsChart::class,
        \App\Filament\Widgets\SalesTrendChart::class,
        \App\Filament\Widgets\ClientsTrendChart::class,
        \App\Filament\Widgets\PurchasesTrendChart::class,
        \App\Filament\Widgets\OrdersByStatusChart::class,
        AccountWidget::class,
      ])
      ->middleware([
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        AuthenticateSession::class,
        ShareErrorsFromSession::class,
        PreventRequestForgery::class,
        SubstituteBindings::class,
        DisableBladeIconComponents::class,
        DispatchServingFilamentEvent::class,
      ])
      ->authMiddleware([
        Authenticate::class,
      ]);
  }
}
