<?php

namespace App\Filament\Support;

use App\Enums\BookReleaseDispatchStatus;
use App\Models\Book;
use App\Models\BookReleaseSubscription;
use App\Services\BookRelease\BookReleaseDispatchService;
use App\Services\BookRelease\BookReleaseMessageService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
      ->fillForm(fn (BookReleaseSubscription $record): array => self::defaultMessageFormData($record))
      ->form(fn (BookReleaseSubscription $record): array => self::messageFormSchema($record))
      ->action(function (BookReleaseSubscription $record, array $data): void {
        $log = app(BookReleaseDispatchService::class)->sendToSubscription(
          $record,
          self::selectedMessageId($data),
          self::customSubject($data),
          self::customBody($data),
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

        return self::messageFormSchema($first);
      })
      ->action(function (Collection $records, array $data): void {
        $result = app(BookReleaseDispatchService::class)->sendBulk(
          $records,
          self::selectedMessageId($data),
          self::customSubject($data),
          self::customBody($data),
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
      ->label('Envoyer maintenant')
      ->icon(Heroicon::OutlinedPaperAirplane)
      ->form([
        Select::make('book_id')
          ->label('Livre')
          ->options(fn (): array => Book::query()->orderBy('title')->pluck('title', 'id')->all())
          ->searchable()
          ->required()
          ->live(),
        ...self::messageFields(
          bookResolver: function (callable $get): ?Book {
            $bookId = $get('book_id');

            if (! $bookId) {
              return null;
            }

            return Book::query()->find($bookId);
          },
          subscriptionResolver: fn (): ?BookReleaseSubscription => null,
        ),
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
          self::customSubject($data),
          self::customBody($data),
        );

        Notification::make()
          ->title('Campagne envoyée')
          ->body($result['sent'].' envoyé(s), '.$result['failed'].' échec(s).')
          ->success()
          ->send();
      });
  }

  /**
   * Action de programmation d'envoi aux inscrits non notifiés.
   *
   * @return Action Action Filament
   */
  public static function scheduleEmailCampaign(): Action
  {
    return Action::make('scheduleReleaseEmail')
      ->label('Programmer l\'envoi')
      ->icon(Heroicon::OutlinedClock)
      ->form([
        Select::make('book_id')
          ->label('Livre')
          ->options(fn (): array => Book::query()->orderBy('title')->pluck('title', 'id')->all())
          ->searchable()
          ->required()
          ->live(),
        DateTimePicker::make('scheduled_at')
          ->label('Date et heure d\'envoi')
          ->seconds(false)
          ->required()
          ->minDate(now())
          ->helperText('L\'envoi démarre automatiquement à cette date (tâche planifiée chaque minute).'),
        ...self::messageFields(
          bookResolver: function (callable $get): ?Book {
            $bookId = $get('book_id');

            if (! $bookId) {
              return null;
            }

            return Book::query()->find($bookId);
          },
          subscriptionResolver: fn (): ?BookReleaseSubscription => null,
        ),
      ])
      ->action(function (array $data): void {
        $book = Book::query()->findOrFail($data['book_id']);

        $book->update([
          'release_auto_notify_enabled' => true,
          'release_auto_notify_at' => $data['scheduled_at'],
          'release_auto_notify_sent_at' => null,
          'release_auto_notify_message_id' => self::selectedMessageId($data),
          'release_auto_notify_email_subject' => self::customSubject($data),
          'release_auto_notify_email_body' => self::customBody($data),
        ]);

        Notification::make()
          ->title('Envoi programmé')
          ->body('Les inscrits non notifiés recevront l\'e-mail le '
            .\Illuminate\Support\Carbon::parse($data['scheduled_at'])->locale('fr')->isoFormat('D MMMM YYYY à HH:mm').'.')
          ->success()
          ->send();
      });
  }

  /**
   * Schéma complet de sélection et édition du message.
   *
   * @param BookReleaseSubscription|null $record Inscription source
   * @return list<Select|TextInput|Textarea>
   */
  private static function messageFormSchema(?BookReleaseSubscription $record): array
  {
    return self::messageFields(
      bookResolver: fn (): ?Book => $record?->book,
      subscriptionResolver: fn (): ?BookReleaseSubscription => $record,
    );
  }

  /**
   * Champs de sélection, prévisualisation et édition du message.
   *
   * @param callable(): ?Book $bookResolver Résout le livre cible
   * @param callable(): ?BookReleaseSubscription $subscriptionResolver Résout l'inscription cible
   * @return list<Select|TextInput|Textarea>
   */
  private static function messageFields(
    callable $bookResolver,
    callable $subscriptionResolver,
  ): array {
    $messageService = app(BookReleaseMessageService::class);

    return [
      Select::make('message_id')
        ->label('Modèle de message')
        ->options(function (callable $get) use ($bookResolver, $messageService): array {
          $book = $bookResolver($get);

          return $messageService->optionsForBook($book);
        })
        ->required()
        ->live()
        ->afterStateUpdated(function (?string $state, callable $set, callable $get) use (
          $bookResolver,
          $subscriptionResolver,
          $messageService,
        ): void {
          $book = $bookResolver($get);

          if ($book === null || $state === null || $state === '') {
            return;
          }

          $subscription = $subscriptionResolver($get) ?? new BookReleaseSubscription([
            'email' => 'exemple@email.com',
          ]);

          $set('email_subject', $messageService->resolveEmailSubject($book, $subscription, $state));
          $set('email_body', $messageService->resolveBody($book, $subscription, $state));
        })
        ->native(false),
      TextInput::make('email_subject')
        ->label('Objet de l\'e-mail')
        ->required()
        ->maxLength(255)
        ->columnSpanFull(),
      Textarea::make('email_body')
        ->label('Contenu du message')
        ->required()
        ->rows(10)
        ->helperText('Vous pouvez modifier le texte avant l\'envoi. Les variables seront remplacées pour chaque destinataire si vous les conservez.')
        ->columnSpanFull(),
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

  /**
   * Retourne l'objet personnalisé saisi dans le formulaire.
   *
   * @param array<string, mixed> $data Données du formulaire
   * @return string|null Objet e-mail
   */
  private static function customSubject(array $data): ?string
  {
    $subject = trim((string) ($data['email_subject'] ?? ''));

    return $subject !== '' ? $subject : null;
  }

  /**
   * Retourne le corps personnalisé saisi dans le formulaire.
   *
   * @param array<string, mixed> $data Données du formulaire
   * @return string|null Corps du message
   */
  private static function customBody(array $data): ?string
  {
    $body = trim((string) ($data['email_body'] ?? ''));

    return $body !== '' ? $body : null;
  }

  /**
   * Pré-remplit le formulaire avec le premier modèle disponible.
   *
   * @param BookReleaseSubscription|null $record Inscription source
   * @return array<string, string> Valeurs initiales
   */
  private static function defaultMessageFormData(?BookReleaseSubscription $record): array
  {
    $messageService = app(BookReleaseMessageService::class);
    $book = $record?->book;
    $options = $messageService->optionsForBook($book);
    $messageId = array_key_first($options);

    if (! is_string($messageId) || $messageId === '') {
      return [];
    }

    $subscription = $record ?? new BookReleaseSubscription([
      'email' => 'exemple@email.com',
    ]);

    if ($book === null) {
      return [
        'message_id' => $messageId,
      ];
    }

    return [
      'message_id' => $messageId,
      'email_subject' => $messageService->resolveEmailSubject($book, $subscription, $messageId),
      'email_body' => $messageService->resolveBody($book, $subscription, $messageId),
    ];
  }
}
