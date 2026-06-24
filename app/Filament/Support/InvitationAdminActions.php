<?php

namespace App\Filament\Support;

use App\Enums\InvitationDispatchChannel;
use App\Enums\InvitationRsvpStatus;
use App\Models\Event;
use App\Models\Invitation;
use App\Services\Invitations\InvitationDispatchService;
use App\Services\Invitations\InvitationGuestExportService;
use App\Services\Invitations\InvitationGuestImportService;
use App\Services\Invitations\InvitationLinkService;
use App\Services\Invitations\InvitationMessageService;
use App\Filament\Support\InvitationSmsPreviewHelper;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Js;
use Livewire\Component;
use RuntimeException;
use BackedEnum;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Actions Filament pour l'envoi et le suivi des invitations.
 */
class InvitationAdminActions
{
  /**
   * Action d'envoi email pour une invitation.
   *
   * @return Action Action Filament
   */
  public static function sendEmail(): Action
  {
    return self::configureChannelAction(
      name: 'sendEmail',
      label: 'Email',
      icon: Heroicon::OutlinedEnvelope,
      channel: InvitationDispatchChannel::Email,
      visible: fn (Invitation $record): bool => self::recordCanUseChannel($record, InvitationDispatchChannel::Email),
      heading: 'Envoyer par email',
      submitLabel: 'Envoyer',
      handler: function (Invitation $record, array $data): void {
        app(InvitationDispatchService::class)->sendEmail(
          $record,
          self::selectedMessageId($data),
        );
      },
      successTitle: 'Invitation envoyée par email',
    );
  }

  /**
   * Action d'ouverture WhatsApp pour une invitation.
   *
   * @return Action Action Filament
   */
  public static function openWhatsapp(): Action
  {
    return self::configureChannelAction(
      name: 'openWhatsapp',
      label: 'WhatsApp',
      icon: Heroicon::OutlinedChatBubbleLeftRight,
      channel: InvitationDispatchChannel::Whatsapp,
      visible: fn (Invitation $record): bool => self::recordCanUseChannel($record, InvitationDispatchChannel::Whatsapp),
      heading: 'Envoyer via WhatsApp',
      submitLabel: 'Ouvrir WhatsApp',
      handler: function (Invitation $record, array $data, Action $action): void {
        $messageId = self::selectedMessageId($data);
        $url = app(InvitationDispatchService::class)->whatsappUrl($record, $messageId);

        if ($url === null) {
          throw new RuntimeException('Numéro de téléphone invalide.');
        }

        self::openUrlInNewTab($action, $url);
        app(InvitationDispatchService::class)->markWhatsappSent($record, $messageId);
      },
      successTitle: 'WhatsApp ouvert',
      successBody: 'Le message a été préparé dans WhatsApp.',
    );
  }

  /**
   * Action d'envoi SMS pour une invitation.
   *
   * @return Action Action Filament
   */
  public static function openSms(): Action
  {
    return self::configureChannelAction(
      name: 'openSms',
      label: 'SMS',
      icon: Heroicon::OutlinedDevicePhoneMobile,
      channel: InvitationDispatchChannel::Sms,
      visible: fn (Invitation $record): bool => self::recordCanUseChannel($record, InvitationDispatchChannel::Sms),
      heading: fn (): string => app(InvitationDispatchService::class)->usesKecelSms()
        ? 'Envoyer par SMS (Kecel)'
        : 'Envoyer par SMS',
      submitLabel: fn (): string => app(InvitationDispatchService::class)->usesKecelSms()
        ? 'Envoyer le SMS'
        : 'Ouvrir SMS',
      handler: function (Invitation $record, array $data, Action $action): void {
        $result = app(InvitationDispatchService::class)->sendSms(
          $record,
          self::selectedMessageId($data),
        );

        if ($result['mode'] === 'manual' && filled($result['url']) && ! app()->environment('production')) {
          self::openUrlInNewTab($action, $result['url']);
        }
      },
      successTitle: fn (): string => app(InvitationDispatchService::class)->usesKecelSms()
        ? 'SMS envoyé via Kecel'
        : 'SMS ouvert',
      successBody: fn (): string => app(InvitationDispatchService::class)->usesKecelSms()
        ? 'Le SMS a été transmis à l\'API Kecel.'
        : 'Le message a été préparé dans votre application SMS.',
    );
  }

  /**
   * Action de consultation de la réponse RSVP et du commentaire invité.
   *
   * @return Action Action Filament
   */
  public static function viewRsvpResponse(): Action
  {
    return Action::make('viewRsvpResponse')
      ->label('Réponse')
      ->icon(Heroicon::OutlinedChatBubbleBottomCenterText)
      ->color(fn (Invitation $record): string => match ($record->rsvp_status) {
        InvitationRsvpStatus::Attending => 'success',
        InvitationRsvpStatus::NotAttending => 'danger',
        default => 'gray',
      })
      ->visible(fn (Invitation $record): bool => $record->responded_at !== null)
      ->modalHeading(fn (Invitation $record): string => 'Réponse de '.$record->full_name)
      ->modalSubmitAction(false)
      ->modalCancelActionLabel('Fermer')
      ->modalWidth('lg')
      ->form(fn (Invitation $record): array => [
        Placeholder::make('rsvp_status')
          ->label('Statut RSVP')
          ->content($record->rsvp_status?->label() ?? '—'),
        Placeholder::make('responded_at')
          ->label('Répondu le')
          ->content($record->responded_at?->format('d/m/Y H:i') ?? '—'),
        Textarea::make('guest_message')
          ->label('Commentaire de l\'invité')
          ->default($record->guest_message ?: 'Aucun commentaire.')
          ->disabled()
          ->dehydrated(false)
          ->rows(5)
          ->columnSpanFull(),
      ]);
  }

  /**
   * Télécharge le modèle Excel pour importer des invités.
   *
   * @return Action Action Filament
   */
  public static function downloadGuestImportTemplate(): Action
  {
    return Action::make('downloadGuestImportTemplate')
      ->label('Modèle Excel')
      ->icon(Heroicon::OutlinedDocumentArrowDown)
      ->color('gray')
      ->action(function (): StreamedResponse {
        $path = app(InvitationGuestImportService::class)->generateTemplate();

        return response()->streamDownload(function () use ($path): void {
          readfile($path);
          @unlink($path);
        }, 'modele-invites.xlsx', [
          'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
      });
  }

  /**
   * Importe une liste d'invités depuis un fichier Excel pour un événement.
   *
   * @param callable(): Event $eventResolver Fournit l'événement cible
   * @return Action Action Filament
   */
  public static function importGuestsFromExcel(callable $eventResolver): Action
  {
    return Action::make('importGuestsFromExcel')
      ->label('Importer Excel')
      ->icon(Heroicon::OutlinedArrowUpTray)
      ->color('primary')
      ->modalHeading('Importer des invités')
      ->modalDescription('Téléchargez d\'abord le modèle Excel, remplissez-le, puis importez-le ici. Les invités non enregistrés seront listés dans la notification et un fichier Excel récapitulatif (avec les raisons) sera proposé au téléchargement.')
      ->modalSubmitActionLabel('Importer')
      ->modalWidth('lg')
      ->form([
        FileUpload::make('file')
          ->label('Fichier Excel (.xlsx)')
          ->disk('local')
          ->directory('invitation-imports')
          ->visibility('private')
          ->acceptedFileTypes([
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
          ])
          ->required()
          ->maxSize(5120),
      ])
      ->action(function (array $data) use ($eventResolver): ?StreamedResponse {
        $relativePath = $data['file'] ?? null;

        if (! is_string($relativePath) || $relativePath === '') {
          Notification::make()
            ->title('Fichier manquant')
            ->danger()
            ->send();

          return null;
        }

        $diskPath = Storage::disk('local')->path($relativePath);

        if (! is_file($diskPath)) {
          Notification::make()
            ->title('Impossible de lire le fichier importé')
            ->danger()
            ->send();

          return null;
        }

        try {
          $importService = app(InvitationGuestImportService::class);
          $result = $importService->import(
            $eventResolver(),
            $diskPath,
          );
        } catch (RuntimeException $exception) {
          Notification::make()
            ->title($exception->getMessage())
            ->danger()
            ->send();

          return null;
        } finally {
          Storage::disk('local')->delete($relativePath);
        }

        if ($result['notRegistered'] === []) {
          Notification::make()
            ->title('Import terminé')
            ->body($importService->formatImportSummary($result))
            ->success()
            ->send();

          return null;
        }

        $exportPath = $importService->generateNotRegisteredExport($result['notRegistered']);
        $exportFilename = basename($exportPath);

        session(['invitation_import_reject_file' => $exportFilename]);

        Notification::make()
          ->title('Import terminé')
          ->body($importService->formatImportSummary($result))
          ->warning()
          ->persistent()
          ->actions([
            Action::make('downloadRejectedGuests')
              ->label('Télécharger le rapport Excel')
              ->button()
              ->url(route('admin.invitation-import-rejects'))
              ->openUrlInNewTab(),
          ])
          ->send();

        return response()->streamDownload(function () use ($exportPath): void {
          readfile($exportPath);
        }, $exportFilename, [
          'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
      });
  }

  /**
   * Action de copie du lien d'invitation dans le presse-papiers.
   *
   * @return Action Action Filament
   */
  public static function copyLink(): Action
  {
    return Action::make('copyLink')
      ->label('Copier lien')
      ->icon(Heroicon::OutlinedLink)
      ->tooltip('Copie le lien court (/i/token) utilisé dans les SMS et WhatsApp')
      ->action(function (Invitation $record, Action $action): void {
        $linkService = app(InvitationLinkService::class);
        $url = $linkService->publicUrl($record);
        $livewire = $action->getLivewire();

        if ($livewire instanceof Component) {
          $livewire->js('navigator.clipboard.writeText('.Js::from($url).')');
        }

        Notification::make()
          ->title('Lien court copié')
          ->body($url)
          ->success()
          ->send();
      });
  }

  /**
   * Actions groupées d'envoi filtrées selon les canaux activés pour l'événement.
   *
   * @param Event|null $eventContext Événement connu (relation manager) ou null (liste globale)
   * @return list<BulkAction> Actions groupées disponibles
   */
  public static function bulkActionsForEvent(?Event $eventContext = null): array
  {
    $messageService = app(InvitationMessageService::class);
    $channels = $eventContext !== null
      ? $messageService->enabledChannels($eventContext)
      : InvitationDispatchChannel::cases();

    $actions = [];

    foreach ($channels as $channel) {
      $actions[] = match ($channel) {
        InvitationDispatchChannel::Email => self::bulkSendEmail($eventContext),
        InvitationDispatchChannel::Sms => self::bulkSendSms($eventContext),
        InvitationDispatchChannel::Whatsapp => self::bulkSendWhatsapp($eventContext),
      };
    }

    array_unshift($actions, self::bulkExportSelectedExcel());

    return $actions;
  }

  /**
   * Exporte les invités sélectionnés en Excel avec token et lien court.
   *
   * @return BulkAction Action groupée
   */
  public static function bulkExportSelectedExcel(): BulkAction
  {
    return BulkAction::make('bulkExportSelectedExcel')
      ->label('Exporter la sélection (Excel)')
      ->icon(Heroicon::OutlinedArrowDownTray)
      ->color('success')
      ->deselectRecordsAfterCompletion()
      ->action(function (Collection $records): ?StreamedResponse {
        if ($records->isEmpty()) {
          Notification::make()
            ->title('Aucun invité sélectionné')
            ->warning()
            ->send();

          return null;
        }

        $path = app(InvitationGuestExportService::class)->exportSelection($records);

        return response()->streamDownload(function () use ($path): void {
          readfile($path);
          @unlink($path);
        }, basename($path), [
          'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
      });
  }

  /**
   * Envoi email en masse.
   *
   * @return BulkAction Action groupée
   */
  public static function bulkSendEmail(?Event $eventContext = null): BulkAction
  {
    return self::configureBulkChannelAction(
      name: 'bulkSendEmail',
      label: 'Envoyer par email',
      icon: Heroicon::OutlinedEnvelope,
      channel: InvitationDispatchChannel::Email,
      heading: 'Envoyer par email',
      submitLabel: 'Envoyer',
      successLabel: 'invitation(s) envoyée(s) par email',
      eventContext: $eventContext,
    );
  }

  /**
   * Envoi SMS en masse.
   *
   * @return BulkAction Action groupée
   */
  public static function bulkSendSms(?Event $eventContext = null): BulkAction
  {
    return self::configureBulkChannelAction(
      name: 'bulkSendSms',
      label: 'Envoyer par SMS',
      icon: Heroicon::OutlinedDevicePhoneMobile,
      channel: InvitationDispatchChannel::Sms,
      heading: fn (): string => app(InvitationDispatchService::class)->usesKecelSms()
        ? 'Envoyer par SMS (Kecel)'
        : 'Envoyer par SMS',
      submitLabel: 'Envoyer',
      successLabel: 'invitation(s) traitée(s) par SMS',
      eventContext: $eventContext,
      afterBulk: function (array $result, Action $action): void {
        if ($result['urls'] !== [] && ! app()->environment('production')) {
          self::openUrlsInNewTabs($action, $result['urls']);
        }
      },
    );
  }

  /**
   * Envoi WhatsApp en masse.
   *
   * @return BulkAction Action groupée
   */
  public static function bulkSendWhatsapp(?Event $eventContext = null): BulkAction
  {
    return self::configureBulkChannelAction(
      name: 'bulkSendWhatsapp',
      label: 'Envoyer par WhatsApp',
      icon: Heroicon::OutlinedChatBubbleLeftRight,
      channel: InvitationDispatchChannel::Whatsapp,
      heading: 'Envoyer via WhatsApp',
      submitLabel: 'Préparer les conversations',
      successLabel: 'conversation(s) WhatsApp préparée(s)',
      eventContext: $eventContext,
      afterBulk: function (array $result): void {
        self::notifyWhatsappLinks($result['whatsappLinks'] ?? []);
      },
    );
  }

  /**
   * Groupe d'actions « Envoyer à tous » filtré selon les canaux activés de l'événement.
   *
   * @param callable(): Event $eventResolver Fournit l'événement cible
   * @param callable(InvitationDispatchChannel): Collection<int, Invitation> $invitationsResolver Fournit les invitations par canal
   * @return ActionGroup Groupe d'actions Filament
   */
  public static function sendAllForEventActionGroup(
    callable $eventResolver,
    callable $invitationsResolver,
  ): ActionGroup {
    $event = $eventResolver();
    $messageService = app(InvitationMessageService::class);
    $actions = [];

    foreach ($messageService->enabledChannels($event) as $channel) {
      $actions[] = self::sendAllForEvent(
        $channel,
        fn () => $invitationsResolver($channel),
      );
    }

    return ActionGroup::make($actions)
      ->label('Envoyer à tous')
      ->icon(Heroicon::OutlinedPaperAirplane)
      ->visible($actions !== []);
  }

  /**
   * Action d'envoi groupé à tous les invités d'un événement.
   *
   * @param InvitationDispatchChannel $channel Canal d'envoi
   * @param callable(): Collection<int, Invitation> $invitationsResolver Fournit les invitations cibles
   * @return Action Action Filament
   */
  public static function sendAllForEvent(
    InvitationDispatchChannel $channel,
    callable $invitationsResolver,
  ): Action {
    $labels = [
      InvitationDispatchChannel::Email->value => ['Envoyer email à tous', 'Envoyer par email à tous les invités', 'Envoyer', Heroicon::OutlinedEnvelope],
      InvitationDispatchChannel::Sms->value => ['Envoyer SMS à tous', 'Envoyer par SMS à tous les invités avec téléphone', 'Envoyer', Heroicon::OutlinedDevicePhoneMobile],
      InvitationDispatchChannel::Whatsapp->value => ['Envoyer WhatsApp à tous', 'Préparer WhatsApp pour tous les invités avec téléphone', 'Ouvrir WhatsApp', Heroicon::OutlinedChatBubbleLeftRight],
    ];

    [$label, $heading, $submit, $icon] = $labels[$channel->value];

    return Action::make('sendAll'.$channel->value)
      ->label($label)
      ->icon($icon)
      ->visible(function () use ($invitationsResolver, $channel): bool {
        $first = $invitationsResolver()->first();

        if (! $first instanceof Invitation) {
          return false;
        }

        $first->loadMissing('event');

        return app(InvitationMessageService::class)->isChannelEnabled($first->event, $channel);
      })
      ->modal(fn (): bool => self::eventHasMessageTemplates($invitationsResolver, $channel))
      ->form(fn (): array => self::eventMessageTemplateFields($invitationsResolver, $channel))
      ->fillForm(fn (): array => self::eventDefaultMessageFormData($invitationsResolver, $channel))
      ->modalHeading($heading)
      ->modalSubmitActionLabel($submit)
      ->modalWidth('lg')
      ->action(function (array $data, Action $action) use ($invitationsResolver, $channel): void {
        $invitations = $invitationsResolver();
        $messageId = self::selectedMessageId($data);
        $result = app(InvitationDispatchService::class)->sendBulk($invitations, $channel, $messageId);

        if ($channel === InvitationDispatchChannel::Sms && $result['urls'] !== [] && ! app()->environment('production')) {
          self::openUrlsInNewTabs($action, $result['urls']);
        }

        if ($channel === InvitationDispatchChannel::Whatsapp) {
          self::notifyWhatsappLinks($result['whatsappLinks'] ?? []);
        }

        $notification = Notification::make()
          ->title($result['sent'].' envoi(s) réussi(s), '.$result['failed'].' échec(s)');

        if ($result['failed'] > 0 && $result['sent'] === 0) {
          $notification->danger();
        } elseif ($result['failed'] > 0) {
          $notification->warning();
        } else {
          $notification->success();
        }

        $notification->send();
      });
  }

  /**
   * Action de programmation des rappels pour un événement.
   *
   * @param callable(): Event $eventResolver Fournit l'événement cible
   * @return Action Action Filament
   */
  public static function scheduleSendForEvent(callable $eventResolver): Action
  {
    return Action::make('scheduleInvitationSend')
      ->label('Programmer les rappels')
      ->icon(Heroicon::OutlinedClock)
      ->modalWidth('lg')
      ->form(function () use ($eventResolver): array {
        $event = $eventResolver();

        return [
          DateTimePicker::make('scheduled_at')
            ->label('Date et heure d\'envoi')
            ->seconds(false)
            ->required()
            ->minDate(now())
            ->default($event->invitation_auto_send_at),
          Select::make('channel')
            ->label('Canal')
            ->options(app(InvitationMessageService::class)->enabledChannelOptions($event))
            ->default($event->invitation_auto_send_channel ?? InvitationDispatchChannel::Email->value)
            ->required()
            ->live()
            ->native(false),
          Select::make('message_id')
            ->label('Modèle de message')
            ->options(function (callable $get) use ($event): array {
              $channel = InvitationDispatchChannel::tryFrom((string) $get('channel'))
                ?? InvitationDispatchChannel::Email;

              return app(InvitationMessageService::class)->optionsForChannel($event, $channel);
            })
            ->required()
            ->default($event->invitation_auto_send_message_id)
            ->native(false),
        ];
      })
      ->action(function (array $data) use ($eventResolver): void {
        $event = $eventResolver();

        $event->update([
          'invitation_auto_send_enabled' => true,
          'invitation_auto_send_at' => $data['scheduled_at'],
          'invitation_auto_send_sent_at' => null,
          'invitation_auto_send_channel' => $data['channel'],
          'invitation_auto_send_message_id' => self::selectedMessageId($data),
        ]);

        Notification::make()
          ->title('Rappels programmés')
          ->body('Les invités éligibles seront contactés automatiquement à la date choisie.')
          ->success()
          ->send();
      });
  }

  /**
   * Configure une action unitaire d'envoi par canal.
   *
   * @param string $name Identifiant de l'action
   * @param string $label Libellé du bouton
   * @param BackedEnum|string $icon Icône Filament
   * @param InvitationDispatchChannel $channel Canal cible
   * @param callable $visible Condition de visibilité
   * @param string|callable $heading Titre de la modale
   * @param string|callable $submitLabel Libellé du bouton de validation
   * @param callable $handler Logique d'envoi
   * @param string|callable $successTitle Titre de notification succès
   * @param string|callable|null $successBody Corps de notification succès
   * @return Action Action configurée
   */
  private static function configureChannelAction(
    string $name,
    string $label,
    BackedEnum|string $icon,
    InvitationDispatchChannel $channel,
    callable $visible,
    string|callable $heading,
    string|callable $submitLabel,
    callable $handler,
    string|callable $successTitle,
    string|callable|null $successBody = null,
  ): Action {
    return Action::make($name)
      ->label($label)
      ->icon($icon)
      ->visible($visible)
      ->modal(fn (Invitation $record): bool => app(InvitationMessageService::class)->hasTemplatesForChannel(
        $record->event,
        $channel,
      ))
      ->form(fn (Invitation $record): array => self::messageTemplateFields($record, $channel))
      ->fillForm(fn (Invitation $record): array => self::defaultMessageFormData($record, $channel))
      ->modalHeading($heading)
      ->modalSubmitActionLabel($submitLabel)
      ->modalWidth('lg')
      ->action(function (Invitation $record, array $data, Action $action) use ($handler, $successTitle, $successBody): void {
        try {
          $handler($record, $data, $action);

          $notification = Notification::make()
            ->title(is_callable($successTitle) ? $successTitle() : $successTitle)
            ->success();

          if ($successBody !== null) {
            $notification->body(is_callable($successBody) ? $successBody() : $successBody);
          }

          $notification->send();
        } catch (RuntimeException $exception) {
          Notification::make()
            ->title($exception->getMessage())
            ->danger()
            ->send();
        }
      });
  }

  /**
   * Configure une action groupée d'envoi par canal.
   *
   * @param string $name Identifiant de l'action
   * @param string $label Libellé du bouton
   * @param BackedEnum|string $icon Icône Filament
   * @param InvitationDispatchChannel $channel Canal cible
   * @param string|callable $heading Titre de la modale
   * @param string $submitLabel Libellé du bouton de validation
   * @param string $successLabel Libellé de succès
   * @param callable|null $afterBulk Callback post-traitement
   * @return BulkAction Action groupée configurée
   */
  private static function configureBulkChannelAction(
    string $name,
    string $label,
    BackedEnum|string $icon,
    InvitationDispatchChannel $channel,
    string|callable $heading,
    string $submitLabel,
    string $successLabel,
    ?Event $eventContext = null,
    ?callable $afterBulk = null,
  ): BulkAction {
    return BulkAction::make($name)
      ->label($label)
      ->icon($icon)
      ->modal(fn (Collection $records): bool => self::bulkHasMessageTemplates($records, $channel))
      ->form(fn (Collection $records): array => self::bulkMessageTemplateFields($records, $channel))
      ->fillForm(fn (Collection $records): array => self::bulkDefaultMessageFormData($records, $channel))
      ->modalHeading($heading)
      ->modalSubmitActionLabel($submitLabel)
      ->action(function (Collection $records, array $data, Action $action) use ($channel, $successLabel, $afterBulk, $eventContext): void {
        $event = $eventContext ?? self::resolveEventFromRecords($records);

        if ($event === null) {
          Notification::make()
            ->title('Événement introuvable')
            ->body('Sélectionnez des invitations du même événement.')
            ->danger()
            ->send();

          return;
        }

        if (! app(InvitationMessageService::class)->isChannelEnabled($event, $channel)) {
          Notification::make()
            ->title('Canal non activé')
            ->body('Ce canal n\'est pas activé dans les modèles de message de cet événement.')
            ->danger()
            ->send();

          return;
        }

        $messageId = self::selectedMessageId($data);
        $eligible = $records->filter(function ($record) use ($channel): bool {
          if (! $record instanceof Invitation) {
            return false;
          }

          return self::recordCanUseChannel($record, $channel);
        });

        if ($eligible->isEmpty()) {
          Notification::make()
            ->title('Aucun invité éligible')
            ->body('Vérifiez que les invités sélectionnés ont les coordonnées requises pour ce canal.')
            ->warning()
            ->send();

          return;
        }

        $result = app(InvitationDispatchService::class)->sendBulk($eligible, $channel, $messageId);

        if ($afterBulk !== null) {
          $afterBulk($result, $action);
        }

        $notification = Notification::make()
          ->title($result['sent'].' '.$successLabel.' ('.$result['failed'].' échec(s))');

        if ($result['failed'] > 0 && $result['sent'] === 0) {
          $notification->danger();
        } elseif ($result['failed'] > 0) {
          $notification->warning();
        } else {
          $notification->success();
        }

        $notification->send();
      });
  }

  /**
   * Champs de sélection du modèle de message pour une invitation.
   *
   * @param Invitation $record Invitation cible
   * @param InvitationDispatchChannel $channel Canal d'envoi
   * @return list<Select|Placeholder> Champs du formulaire modal
   */
  private static function messageTemplateFields(
    Invitation $record,
    InvitationDispatchChannel $channel,
  ): array {
    $record->loadMissing('event');
    $options = app(InvitationMessageService::class)->optionsForChannel($record->event, $channel);
    $fields = [self::placeholderField()];

    if ($options !== []) {
      $fields[] = Select::make('message_id')
        ->label('Modèle de message')
        ->options($options)
        ->required()
        ->live()
        ->native(false);
    }

    if ($channel === InvitationDispatchChannel::Sms) {
      if (! app(InvitationDispatchService::class)->usesKecelSms()) {
        $fields[] = Placeholder::make('sms_api_warning')
          ->label('Configuration SMS')
          ->content(app()->environment('production')
            ? 'L\'API Kecel n\'est pas active sur ce serveur. Les SMS ne partiront pas tant que SMS_DRIVER, SMS_TOKEN et SMS_FROM ne sont pas configurés dans le .env.'
            : 'Mode développement : sans API Kecel, le navigateur ouvrira l\'application SMS locale.')
          ->columnSpanFull();
      }

      $fields[] = Placeholder::make('sms_preview')
        ->label('Aperçu SMS et consommation')
        ->content(function (callable $get) use ($record): \Illuminate\Support\HtmlString {
          return InvitationSmsPreviewHelper::previewHtml(
            $record->event,
            self::selectedMessageId(['message_id' => $get('message_id')]),
            $record,
          );
        })
        ->columnSpanFull();
    }

    return $fields;
  }

  /**
   * Champ d'aide affichant les variables dynamiques avec infobulles.
   *
   * @return Placeholder Champ d'aide
   */
  private static function placeholderField(): Placeholder
  {
    return Placeholder::make('variables_help')
      ->label('Variables disponibles')
      ->content(fn () => InvitationPlaceholderHelper::toHtml())
      ->columnSpanFull();
  }

  /**
   * Valeurs par défaut du formulaire de sélection de message.
   *
   * @param Invitation $record Invitation cible
   * @param InvitationDispatchChannel $channel Canal d'envoi
   * @return array<string, string> Données initiales du formulaire
   */
  private static function defaultMessageFormData(
    Invitation $record,
    InvitationDispatchChannel $channel,
  ): array {
    $record->loadMissing('event');
    $options = app(InvitationMessageService::class)->optionsForChannel($record->event, $channel);

    if ($options === []) {
      return [];
    }

    return ['message_id' => (string) array_key_first($options)];
  }

  /**
   * Indique si un envoi groupé peut proposer des modèles personnalisés.
   *
   * @param Collection $records Invitations sélectionnées
   * @param InvitationDispatchChannel $channel Canal d'envoi
   * @return bool True si un sélecteur de modèle est pertinent
   */
  private static function bulkHasMessageTemplates(
    Collection $records,
    InvitationDispatchChannel $channel,
  ): bool {
    $eventIds = $records
      ->filter(fn ($record): bool => $record instanceof Invitation)
      ->pluck('event_id')
      ->unique()
      ->values();

    if ($eventIds->count() !== 1 || ! $records->first() instanceof Invitation) {
      return false;
    }

    return app(InvitationMessageService::class)->hasTemplatesForChannel(
      $records->first()->event,
      $channel,
    );
  }

  /**
   * Champs de sélection du modèle pour un envoi groupé.
   *
   * @param Collection $records Invitations sélectionnées
   * @param InvitationDispatchChannel $channel Canal d'envoi
   * @return list<Select|Placeholder> Champs du formulaire modal
   */
  private static function bulkMessageTemplateFields(
    Collection $records,
    InvitationDispatchChannel $channel,
  ): array {
    $eventIds = $records
      ->filter(fn ($record): bool => $record instanceof Invitation)
      ->pluck('event_id')
      ->unique()
      ->values();

    if ($eventIds->count() !== 1 || ! $records->first() instanceof Invitation) {
      return [self::placeholderField()];
    }

    return self::messageTemplateFields($records->first(), $channel);
  }

  /**
   * Valeurs par défaut du formulaire pour un envoi groupé.
   *
   * @param Collection $records Invitations sélectionnées
   * @param InvitationDispatchChannel $channel Canal d'envoi
   * @return array<string, string> Données initiales du formulaire
   */
  private static function bulkDefaultMessageFormData(
    Collection $records,
    InvitationDispatchChannel $channel,
  ): array {
    $eventIds = $records
      ->filter(fn ($record): bool => $record instanceof Invitation)
      ->pluck('event_id')
      ->unique()
      ->values();

    if ($eventIds->count() !== 1 || ! $records->first() instanceof Invitation) {
      return [];
    }

    return self::defaultMessageFormData($records->first(), $channel);
  }

  /**
   * Indique si l'événement possède des modèles pour un envoi global.
   *
   * @param callable(): Collection<int, Invitation> $invitationsResolver Fournit les invitations
   * @param InvitationDispatchChannel $channel Canal d'envoi
   * @return bool True si un sélecteur est pertinent
   */
  private static function eventHasMessageTemplates(
    callable $invitationsResolver,
    InvitationDispatchChannel $channel,
  ): bool {
    $first = $invitationsResolver()->first();

    if (! $first instanceof Invitation) {
      return false;
    }

    return app(InvitationMessageService::class)->hasTemplatesForChannel($first->event, $channel);
  }

  /**
   * Champs de sélection du modèle pour un envoi global à l'événement.
   *
   * @param callable(): Collection<int, Invitation> $invitationsResolver Fournit les invitations
   * @param InvitationDispatchChannel $channel Canal d'envoi
   * @return list<Select|Placeholder> Champs du formulaire modal
   */
  private static function eventMessageTemplateFields(
    callable $invitationsResolver,
    InvitationDispatchChannel $channel,
  ): array {
    $first = $invitationsResolver()->first();

    if (! $first instanceof Invitation) {
      return [self::placeholderField()];
    }

    return self::messageTemplateFields($first, $channel);
  }

  /**
   * Valeurs par défaut du formulaire pour un envoi global à l'événement.
   *
   * @param callable(): Collection<int, Invitation> $invitationsResolver Fournit les invitations
   * @param InvitationDispatchChannel $channel Canal d'envoi
   * @return array<string, string> Données initiales du formulaire
   */
  private static function eventDefaultMessageFormData(
    callable $invitationsResolver,
    InvitationDispatchChannel $channel,
  ): array {
    $first = $invitationsResolver()->first();

    if (! $first instanceof Invitation) {
      return [];
    }

    return self::defaultMessageFormData($first, $channel);
  }

  /**
   * Extrait l'identifiant du modèle choisi dans le formulaire.
   *
   * @param array<string, mixed> $data Données du formulaire
   * @return string|null Identifiant du modèle ou null pour le message par défaut
   */
  private static function selectedMessageId(array $data): ?string
  {
    $messageId = $data['message_id'] ?? null;

    if (! is_string($messageId) || $messageId === '' || $messageId === 'default') {
      return null;
    }

    return $messageId;
  }

  /**
   * Indique si une invitation peut utiliser un canal d'envoi.
   *
   * @param Invitation $record Invitation cible
   * @param InvitationDispatchChannel $channel Canal d'envoi
   * @return bool True si l'action doit être visible
   */
  private static function recordCanUseChannel(Invitation $record, InvitationDispatchChannel $channel): bool
  {
    $record->loadMissing('event');

    if (! app(InvitationMessageService::class)->isChannelEnabled($record->event, $channel)) {
      return false;
    }

    return match ($channel) {
      InvitationDispatchChannel::Email => filled($record->email),
      default => filled($record->phone),
    };
  }

  /**
   * Résout l'événement associé à une sélection d'invitations.
   *
   * @param Collection $records Invitations sélectionnées
   * @return Event|null Événement unique ou null
   */
  private static function resolveEventFromRecords(Collection $records): ?Event
  {
    $invitations = $records->filter(fn ($record): bool => $record instanceof Invitation);

    if ($invitations->isEmpty()) {
      return null;
    }

    $eventIds = $invitations->pluck('event_id')->unique();

    if ($eventIds->count() !== 1) {
      return null;
    }

    $first = $invitations->first();
    $first->loadMissing('event');

    return $first->event;
  }

  /**
   * Affiche une notification cliquable pour ouvrir les conversations WhatsApp en masse.
   *
   * @param list<array{url: string, guestName: string}> $links Liens wa.me préparés
   * @return void
   */
  private static function notifyWhatsappLinks(array $links): void
  {
    if ($links === []) {
      return;
    }

    $actions = collect($links)->map(function (array $link, int $index): Action {
      $guestName = $link['guestName'] ?? ('Conversation '.($index + 1));
      $url = $link['url'];

      return Action::make('whatsappLink'.$index)
        ->label($guestName)
        ->url($url, shouldOpenInNewTab: true)
        ->link()
        ->color('success');
    })->all();

    Notification::make()
      ->title(count($links).' conversation(s) WhatsApp prête(s)')
      ->body('Cliquez sur chaque nom ci-dessous pour ouvrir WhatsApp dans un nouvel onglet.')
      ->actions($actions)
      ->persistent()
      ->success()
      ->send();
  }

  /**
   * Ouvre une URL dans un nouvel onglet via Livewire.
   *
   * @param Action $action Action Filament en cours
   * @param string $url URL à ouvrir
   * @return void
   */
  private static function openUrlInNewTab(Action $action, string $url): void
  {
    $livewire = $action->getLivewire();

    if ($livewire instanceof Component) {
      $livewire->js('window.open('.Js::from($url).', "_blank")');
    }
  }

  /**
   * Ouvre plusieurs URLs dans de nouveaux onglets avec un léger délai.
   *
   * @param Action $action Action Filament en cours
   * @param list<string> $urls URLs à ouvrir
   * @return void
   */
  private static function openUrlsInNewTabs(Action $action, array $urls): void
  {
    $livewire = $action->getLivewire();

    if (! $livewire instanceof Component || $urls === []) {
      return;
    }

    $encodedUrls = Js::from($urls);
    $livewire->js(<<<JS
      {$encodedUrls}.forEach((url, index) => {
        setTimeout(() => window.open(url, '_blank'), index * 700);
      });
    JS);
  }
}
