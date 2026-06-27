<?php

namespace App\Filament\Pages;

use App\Services\Invitations\InvitationPhoneLookupExportService;
use App\Services\Invitations\InvitationPhoneLookupService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Page admin pour associer une liste de numéros aux invités enregistrés.
 */
class InvitationPhoneLookup extends Page
{
  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoneArrowUpRight;

  protected static ?string $navigationLabel = 'Correspondance téléphones';

  protected static ?string $title = 'Correspondance téléphones invités';

  protected static string|UnitEnum|null $navigationGroup = 'Événements';

  protected static ?int $navigationSort = 3;

  protected static ?string $slug = 'invitation-phone-lookup';

  protected string $view = 'filament.pages.invitation-phone-lookup';

  public string $phonesRaw = '';

  public ?string $eventId = null;

  public string $statusFilter = 'all';

  /** @var list<array<string, mixed>> */
  public array $results = [];

  /** @var array{total: int, matched: int, unmatched: int} */
  public array $stats = [
    'total' => 0,
    'matched' => 0,
    'unmatched' => 0,
  ];

  /**
   * Restreint l'accès aux utilisateurs autorisés sur les invitations.
   *
   * @return bool True si la page est accessible
   */
  public static function canAccess(): bool
  {
    $user = auth()->user();

    return $user !== null && $user->can('ViewAny:Invitation');
  }

  /**
   * Actions d'en-tête : analyse et export Excel.
   *
   * @return array<int, Action> Actions disponibles
   */
  protected function getHeaderActions(): array
  {
    return [
      Action::make('analyze')
        ->label('Analyser les numéros')
        ->icon(Heroicon::OutlinedMagnifyingGlass)
        ->color('primary')
        ->action(fn () => $this->analyzePhones()),
      Action::make('exportExcel')
        ->label('Télécharger Excel')
        ->icon(Heroicon::OutlinedArrowDownTray)
        ->color('success')
        ->disabled(fn (): bool => $this->results === [])
        ->action(function (): ?StreamedResponse {
          $rows = $this->filteredResults;

          if ($rows === []) {
            Notification::make()
              ->title('Rien à exporter')
              ->body('Ajustez le filtre ou relancez une analyse.')
              ->warning()
              ->send();

            return null;
          }

          return $this->streamExportDownload($rows);
        }),
    ];
  }

  /**
   * Lance la correspondance entre la liste saisie et les invités.
   *
   * @return void
   */
  public function analyzePhones(): void
  {
    $service = app(InvitationPhoneLookupService::class);
    $phones = $service->parsePhoneList($this->phonesRaw);

    if ($phones === []) {
      Notification::make()
        ->title('Aucun numéro saisi')
        ->body('Collez au moins un numéro de téléphone (un par ligne).')
        ->warning()
        ->send();

      $this->results = [];
      $this->stats = ['total' => 0, 'matched' => 0, 'unmatched' => 0];

      return;
    }

    $lookup = $service->lookup($phones, $this->eventId);
    $this->results = $lookup['rows'];
    $this->stats = $lookup['stats'];

    Notification::make()
      ->title('Analyse terminée')
      ->body(sprintf(
        '%d numéro(s) analysé(s) : %d correspondance(s), %d sans nom trouvé.',
        $this->stats['total'],
        $this->stats['matched'],
        $this->stats['unmatched'],
      ))
      ->success()
      ->send();
  }

  /**
   * Retourne les lignes filtrées pour l'affichage tableau.
   *
   * @return list<array<string, mixed>> Lignes visibles
   */
  public function getFilteredResultsProperty(): array
  {
    return app(InvitationPhoneLookupService::class)->filterRows($this->results, $this->statusFilter);
  }

  /**
   * Options de filtre par statut RSVP.
   *
   * @return array<string, string> value => libellé
   */
  public function getStatusFilterOptionsProperty(): array
  {
    return app(InvitationPhoneLookupService::class)->statusFilterOptions();
  }

  /**
   * Options événements pour le filtre optionnel.
   *
   * @return array<string, string> id => titre
   */
  public function getEventOptionsProperty(): array
  {
    return app(InvitationPhoneLookupService::class)->eventOptions();
  }

  /**
   * Exporte les lignes actuellement filtrées.
   *
   * @param list<array<string, mixed>> $rows Lignes à exporter
   * @return StreamedResponse Téléchargement Excel
   */
  private function streamExportDownload(array $rows): StreamedResponse
  {
    $path = app(InvitationPhoneLookupExportService::class)->exportRows($rows);

    return response()->streamDownload(function () use ($path): void {
      readfile($path);
      @unlink($path);
    }, basename($path), [
      'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
  }
}
