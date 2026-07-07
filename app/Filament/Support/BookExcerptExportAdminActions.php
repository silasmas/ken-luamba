<?php

namespace App\Filament\Support;

use App\Models\Book;
use App\Services\Books\Excerpt\BookExcerptExportService;
use App\Support\ExportDownloadResponse;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Actions Filament d'export de l'extrait feuilletable (PDF, Word, EPUB).
 */
class BookExcerptExportAdminActions
{
  /**
   * Groupe d'actions d'export de l'aperçu feuilletable.
   *
   * @return ActionGroup Groupe Filament
   */
  public static function group(): ActionGroup
  {
    return ActionGroup::make([
      self::makeExportAction('exportExcerptPdf', 'PDF', Heroicon::OutlinedDocumentArrowDown, 'pdf'),
      self::makeExportAction('exportExcerptDocx', 'Word (.docx)', Heroicon::OutlinedDocumentText, 'docx'),
      self::makeExportAction('exportExcerptEpub', 'EPUB', Heroicon::OutlinedBookOpen, 'epub'),
    ])
      ->label('Exporter l\'extrait')
      ->icon(Heroicon::OutlinedArrowDownTray)
      ->color('gray')
      ->button();
  }

  /**
   * Crée une action d'export pour un format donné.
   *
   * @param string $name Identifiant de l'action
   * @param string $label Libellé du format
   * @param Heroicon $icon Icône Heroicon Filament
   * @param string $format pdf, docx ou epub
   * @return Action Action configurée
   */
  private static function makeExportAction(string $name, string $label, Heroicon $icon, string $format): Action
  {
    return Action::make($name)
      ->label($label)
      ->icon($icon)
      ->visible(fn (Book $record): bool => self::hasExportableExcerpt($record))
      ->fillForm([
        'includeCovers' => true,
      ])
      ->form(self::exportFormSchema())
      ->action(function (Book $record, array $data) use ($format) {
        $includeCovers = (bool) ($data['includeCovers'] ?? true);
        $service = app(BookExcerptExportService::class);

        try {
          $path = match ($format) {
            'docx' => $service->exportDocx($record, $includeCovers),
            'epub' => $service->exportEpub($record, $includeCovers),
            default => $service->exportPdf($record, $includeCovers),
          };
        } catch (\Throwable $exception) {
          Notification::make()
            ->title('Export impossible')
            ->body($exception->getMessage())
            ->danger()
            ->send();

          return null;
        }

        return ExportDownloadResponse::stream($path);
      });
  }

  /**
   * Schéma du formulaire de confirmation d'export.
   *
   * @return list<Toggle> Champs Filament
   */
  private static function exportFormSchema(): array
  {
    return [
      Toggle::make('includeCovers')
        ->label('Inclure la couverture et la quatrième de couverture')
        ->default(true)
        ->helperText('Si oui, les pages image seront ajoutées au début et à la fin lorsque les visuels sont disponibles.'),
    ];
  }

  /**
   * Indique si le livre possède un extrait exportable.
   *
   * @param Book $record Livre cible
   * @return bool True si des pages existent
   */
  private static function hasExportableExcerpt(Book $record): bool
  {
    return is_array($record->excerpt) && $record->excerpt !== [];
  }
}
