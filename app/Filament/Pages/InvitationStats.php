<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\InvitationResponsesTrendChart;
use App\Filament\Widgets\InvitationRsvpOverviewWidget;
use App\Filament\Widgets\InvitationRsvpStatusChart;
use App\Filament\Widgets\InvitationSentByChannelChart;
use App\Filament\Widgets\InvitationStatsByEventChart;
use App\Models\Event;
use App\Services\Invitations\InvitationAnalyticsService;
use App\Services\Invitations\InvitationRsvpExportService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Page admin des statistiques d'invitations et réponses RSVP par événement.
 */
class InvitationStats extends Page
{
  use HasFiltersForm;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

  protected static ?string $navigationLabel = 'Statistiques invitations';

  protected static ?string $title = 'Statistiques des invitations';

  protected static string|UnitEnum|null $navigationGroup = 'Événements';

  protected static ?int $navigationSort = 2;

  protected static ?string $slug = 'invitation-stats';

  /**
   * Restreint l'accès aux utilisateurs autorisés sur les événements.
   *
   * @return bool True si la page est accessible
   */
  public static function canAccess(): bool
  {
    $user = auth()->user();

    return $user !== null && $user->can('ViewAny:Event');
  }

  /**
   * Initialise les filtres persistés de la page.
   *
   * @return void
   */
  public function mount(): void
  {
    $this->mountHasFilters();
  }

  /**
   * Configure le filtre par événement.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma des filtres
   */
  public function filtersForm(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make('Filtre par événement')
          ->description('Choisissez un événement pour le détail, ou laissez vide pour une vue globale. Les exports Excel et PDF reprennent ce filtre.')
          ->schema([
            Select::make('eventId')
              ->label('Événement')
              ->options(fn (): array => Event::query()
                ->orderByDesc('starts_at')
                ->pluck('title', 'id')
                ->all())
              ->placeholder('Tous les événements')
              ->searchable()
              ->native(false)
              ->live(),
          ])
          ->columns(1)
          ->columnSpanFull(),
      ]);
  }

  /**
   * Compose le contenu principal : filtres, indicateurs et graphiques.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma de la page
   */
  public function content(Schema $schema): Schema
  {
    return $schema
      ->components([
        EmbeddedSchema::make('filtersForm'),
        Grid::make(1)
          ->schema(fn (): array => $this->getWidgetsSchemaComponents($this->getHeaderWidgets())),
        Grid::make($this->getFooterWidgetsColumns())
          ->schema(fn (): array => $this->getWidgetsSchemaComponents($this->getFooterWidgets())),
      ]);
  }

  /**
   * Widgets d'indicateurs en haut de page.
   *
   * @return array<int, class-string> Classes de widgets
   */
  protected function getHeaderWidgets(): array
  {
    return [
      InvitationRsvpOverviewWidget::class,
    ];
  }

  /**
   * Graphiques en bas de page.
   *
   * @return array<int, class-string> Classes de widgets
   */
  protected function getFooterWidgets(): array
  {
    return [
      InvitationRsvpStatusChart::class,
      InvitationSentByChannelChart::class,
      InvitationStatsByEventChart::class,
      InvitationResponsesTrendChart::class,
    ];
  }

  /**
   * Une colonne pour les cartes statistiques.
   *
   * @return int|array<string, int|null> Grille
   */
  public function getHeaderWidgetsColumns(): int|array
  {
    return 1;
  }

  /**
   * Grille responsive pour les graphiques.
   *
   * @return int|array<string, int|null> Grille
   */
  public function getFooterWidgetsColumns(): int|array
  {
    return [
      'default' => 1,
      'md' => 2,
      'xl' => 2,
    ];
  }

  /**
   * Actions d'en-tête : export Excel et PDF des réponses RSVP.
   *
   * @return array<int, Action> Actions disponibles
   */
  protected function getHeaderActions(): array
  {
    return [
      Action::make('exportRsvpExcel')
        ->label('Exporter Excel')
        ->icon(Heroicon::OutlinedArrowDownTray)
        ->color('success')
        ->action(function (): StreamedResponse {
          $this->ensureRespondedInvitationsExist();

          $path = app(InvitationRsvpExportService::class)->exportExcel($this->filters);

          return $this->streamExportDownload($path);
        }),
      Action::make('exportRsvpPdf')
        ->label('Exporter PDF')
        ->icon(Heroicon::OutlinedDocumentArrowDown)
        ->color('gray')
        ->action(function (): StreamedResponse {
          $this->ensureRespondedInvitationsExist();

          $path = app(InvitationRsvpExportService::class)->exportPdf($this->filters);

          return $this->streamExportDownload($path);
        }),
    ];
  }

  /**
   * Vérifie qu'au moins une réponse RSVP existe avant export.
   *
   * @return void
   */
  private function ensureRespondedInvitationsExist(): void
  {
    $eventId = app(InvitationAnalyticsService::class)->resolveEventId($this->filters);
    $count = app(InvitationAnalyticsService::class)->respondedInvitations($eventId)->count();

    if ($count === 0) {
      Notification::make()
        ->title('Aucune réponse à exporter')
        ->body('Aucun invité n\'a encore répondu présent ou absent pour ce filtre.')
        ->warning()
        ->send();

      $this->halt();
    }
  }

  /**
   * Envoie le fichier généré au navigateur puis le supprime.
   *
   * @param string $path Chemin absolu du fichier
   * @return StreamedResponse Téléchargement streamé
   */
  private function streamExportDownload(string $path): StreamedResponse
  {
    return response()->streamDownload(function () use ($path): void {
      readfile($path);
      @unlink($path);
    }, basename($path), [
      'Content-Type' => match (pathinfo($path, PATHINFO_EXTENSION)) {
        'pdf' => 'application/pdf',
        default => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      },
    ]);
  }
}
