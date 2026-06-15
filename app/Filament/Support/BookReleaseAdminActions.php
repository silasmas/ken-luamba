<?php

namespace App\Filament\Support;

use App\Enums\BookReleaseDispatchStatus;
use App\Models\Book;
use App\Models\BookReleaseSubscription;
use App\Services\BookRelease\BookReleaseDispatchService;
use App\Services\BookRelease\BookReleaseMessageService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Actions Filament pour l'envoi des alertes sortie.
 */
class BookReleaseAdminActions
{
  /**
   * Action d'envoi email pour une inscription.
   *
   * @return Action Action Filament
   */
  public static function sendEmail(): Action
  {
    return Action::make('sendReleaseEmail')
      ->label('Envoyer e-mail')
      ->icon(Heroicon::OutlinedEnvelope)
      ->form(fn (BookReleaseSubscription $record): array => self::messageSelectSchema($record))
      ->action(function (BookReleaseSubscription $record, array $data): void {
        $log = app(BookReleaseDispatchService::class)->sendToSubscription(
          $record,
          self::selectedMessageId($data),
        );

        if ($log->status === BookReleaseDispatchStatus::Failed) {
          Notification::make()
            ->title('Échec de l\'envoi')
            ->body($log->error_message ?? 'Impossible d\'envoyer l\'e-mail.')
            ->danger()
            ->send();

          return;
        }

        Notification::make()
          ->title('E-mail envoyé')
          ->body('L\'alerte a été transmise à '.$record->email.'.')
          ->success()
          ->send();
      });
  }

  /**
   * Action groupée d'envoi email.
   *
   * @return BulkAction Action groupée Filament
   */
  public static function sendEmailBulk(): BulkAction
  {
    return BulkAction::make('sendReleaseEmailBulk')
      ->label('Envoyer e-mail')
      ->icon(Heroicon::OutlinedEnvelope)
      ->form(function (Collection $records): array {
        /** @var BookReleaseSubscription|null $first */
        $first = $records->first();

        return self::messageSelectSchema($first);
      })
      ->action(function (Collection $records, array $data): void {
        $result = app(BookReleaseDispatchService::class)->sendBulk(
          $records,
          self::selectedMessageId($data),
        );

        Notification::make()
          ->title('Envoi terminé')
          ->body($result['sent'].' envoyé(s), '.$result['failed'].' échec(s).')
          ->success()
          ->send();
      });
  }

  /**
   * Action d'envoi à tous les inscrits non encore notifiés.
   *
   * @return Action Action Filament
   */
  public static function sendEmailToAllPending(): Action
  {
    return Action::make('sendReleaseEmailAll')
      ->label('Envoyer aux inscrits')
      ->icon(Heroicon::OutlinedPaperAirplane)
      ->form([
        Select::make('book_id')
          ->label('Livre')
          ->options(fn (): array => Book::query()->orderBy('title')->pluck('title', 'id')->all())
          ->searchable()
          ->required()
          ->live(),
        Select::make('message_id')
          ->label('Modèle de message')
          ->options(function (callable $get): array {
            $bookId = $get('book_id');

            if (! $bookId) {
              return app(BookReleaseMessageService::class)->optionsForBook(null);
            }

            $book = Book::query()->find($bookId);

            return app(BookReleaseMessageService::class)->optionsForBook($book);
          })
          ->required()
          ->native(false),
      ])
      ->action(function (array $data): void {
        $subscriptions = BookReleaseSubscription::query()
          ->where('book_id', $data['book_id'])
          ->whereNull('notified_at')
          ->get();

        if ($subscriptions->isEmpty()) {
          Notification::make()
            ->title('Aucun inscrit en attente')
            ->warning()
            ->send();

          return;
        }

        $result = app(BookReleaseDispatchService::class)->sendBulk(
          $subscriptions,
          self::selectedMessageId($data),
        );

        Notification::make()
          ->title('Campagne envoyée')
          ->body($result['sent'].' envoyé(s), '.$result['failed'].' échec(s).')
          ->success()
          ->send();
      });
  }

  /**
   * Schéma de sélection du modèle de message.
   *
   * @param BookReleaseSubscription|null $record Inscription source
   * @return list<Select>
   */
  private static function messageSelectSchema(?BookReleaseSubscription $record): array
  {
    return [
      Select::make('message_id')
        ->label('Modèle de message')
        ->options(fn (): array => app(BookReleaseMessageService::class)->optionsForBook($record?->book))
        ->required()
        ->native(false),
    ];
  }

  /**
   * Retourne l'identifiant du modèle sélectionné.
   *
   * @param array<string, mixed> $data Données du formulaire
   * @return string|null Identifiant du modèle
   */
  private static function selectedMessageId(array $data): ?string
  {
    $messageId = $data['message_id'] ?? null;

    return is_string($messageId) && $messageId !== '' ? $messageId : null;
  }
}
