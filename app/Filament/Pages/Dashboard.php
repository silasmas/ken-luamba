<?php

namespace App\Filament\Pages;

use App\Services\Dashboard\DashboardExportService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tableau de bord principal avec filtres de période et export Excel.
 */
class Dashboard extends BaseDashboard
{
  use HasFiltersForm;

  protected static ?string $title = 'Tableau de bord';

  /**
   * Configure les filtres de période partagés par les widgets.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma des filtres
   */
  public function filtersForm(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make('Période d\'analyse')
          ->description('Filtrez les statistiques et graphiques, puis exportez les données en Excel.')
          ->schema([
            Select::make('period')
              ->label('Période')
              ->options([
                '7d' => '7 derniers jours',
                '30d' => '30 derniers jours',
                '90d' => '90 derniers jours',
                'year' => 'Année en cours',
                'all' => 'Toute la période',
                'custom' => 'Personnalisée',
              ])
              ->default('30d')
              ->native(false)
              ->live(),
            DatePicker::make('startDate')
              ->label('Du')
              ->visible(fn (callable $get): bool => $get('period') === 'custom')
              ->default(now()->subDays(29)),
            DatePicker::make('endDate')
              ->label('Au')
              ->visible(fn (callable $get): bool => $get('period') === 'custom')
              ->default(now()),
          ])
          ->columns([
            'default' => 1,
            'md' => 3,
          ])
          ->columnSpanFull(),
      ]);
  }

  /**
   * Actions d'en-tête : export Excel des données et graphiques.
   *
   * @return array<int, Action> Actions disponibles
   */
  protected function getHeaderActions(): array
  {
    return [
      Action::make('exportExcel')
        ->label('Exporter Excel')
        ->icon(Heroicon::OutlinedArrowDownTray)
        ->color('success')
        ->action(function (): StreamedResponse {
          $path = app(DashboardExportService::class)->export($this->filters);

          return response()->streamDownload(function () use ($path): void {
            readfile($path);
            @unlink($path);
          }, basename($path), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
          ]);
        }),
    ];
  }

  /**
   * Grille responsive des widgets.
   *
   * @return int|array<string, int|null> Colonnes
   */
  public function getColumns(): int|array
  {
    return [
      'default' => 1,
      'md' => 2,
      'xl' => 3,
    ];
  }
}
