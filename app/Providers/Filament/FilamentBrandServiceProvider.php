<?php

namespace App\Providers\Filament;

use App\Services\Admin\AdminAppearanceService;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Personnalise l'apparence du panel admin Filament (couleurs, login).
 */
class FilamentBrandServiceProvider extends ServiceProvider
{
  /**
   * Enregistre les hooks visuels Filament.
   */
  public function boot(): void
  {
    FilamentView::registerRenderHook(
      PanelsRenderHook::HEAD_END,
      function (): string {
        $css = app(AdminAppearanceService::class)->buildCustomCss();

        return Blade::render(<<<'BLADE'
          <style>{!! $css !!}</style>
        BLADE, ['css' => $css]);
      },
    );
  }
}
