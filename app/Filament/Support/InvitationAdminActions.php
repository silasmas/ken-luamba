<?php

namespace App\Filament\Support;

use App\Enums\InvitationDispatchChannel;
use App\Models\Event;
use App\Models\Invitation;
use App\Services\Invitations\InvitationDispatchService;
use App\Services\Invitations\InvitationMessageService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Js;
use Livewire\Component;
use RuntimeException;
use BackedEnum;

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
      visible: fn (Invitation $record): bool => filled($record->email),
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
      visible: fn (Invitation $record): bool => filled($record->phone),
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
      visible: fn (Invitation $record): bool => filled($record->phone),
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

        if ($result['mode'] === 'manual' && filled($result['url'])) {
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
   * Action de copie du lien d'invitation dans le presse-papiers.
   *
   * @return Action Action Filament
   */
  public static function copyLink(): Action
  {
    return Action::make('copyLink')
      ->label('Lien')
      ->icon(Heroicon::OutlinedLink)
      ->action(function (Invitation $record, Action $action): void {
        $url = app(InvitationDispatchService::class)->publicUrl($record);
        $livewire = $action->getLivewire();

        if ($livewire instanceof Component) {
          $livewire->js('navigator.clipboard.writeText('.Js::from($url).')');
        }

        Notification::make()
          ->title('Lien copié')
          ->body('Le lien d\'invitation a été copié dans le presse-papiers.')
          ->success()
          ->send();
      });
  }

  /**
   * Envoi email en masse.
   *
   * @return BulkAction Action groupée
   */
  public static function bulkSendEmail(): BulkAction
  {
    return self::configureBulkChannelAction(
      name: 'bulkSendEmail',
      label: 'Envoyer par email',
      icon: Heroicon::OutlinedEnvelope,
      channel: InvitationDispatchChannel::Email,
      heading: 'Envoyer par email',
      submitLabel: 'Envoyer',
      successLabel: 'invitation(s) envoyée(s) par email',
    );
  }

  /**
   * Envoi SMS en masse.
   *
   * @return BulkAction Action groupée
   */
  public static function bulkSendSms(): BulkAction
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
      afterBulk: function (array $result, Action $action): void {
        if ($result['urls'] !== []) {
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
  public static function bulkSendWhatsapp(): BulkAction
  {
    return self::configureBulkChannelAction(
      name: 'bulkSendWhatsapp',
      label: 'Envoyer par WhatsApp',
      icon: Heroicon::OutlinedChatBubbleLeftRight,
      channel: InvitationDispatchChannel::Whatsapp,
      heading: 'Envoyer via WhatsApp',
      submitLabel: 'Ouvrir WhatsApp',
      successLabel: 'conversation(s) WhatsApp préparée(s)',
      afterBulk: function (array $result, Action $action): void {
        if ($result['urls'] !== []) {
          self::openUrlsInNewTabs($action, $result['urls']);
        }
      },
    );
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

        if (in_array($channel, [InvitationDispatchChannel::Sms, InvitationDispatchChannel::Whatsapp], true) && $result['urls'] !== []) {
          self::openUrlsInNewTabs($action, $result['urls']);
        }

        Notification::make()
          ->title($result['sent'].' envoi(s) réussi(s), '.$result['failed'].' échec(s)')
          ->success()
          ->send();
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
            ->options([
              InvitationDispatchChannel::Email->value => InvitationDispatchChannel::Email->label(),
              InvitationDispatchChannel::Sms->value => InvitationDispatchChannel::Sms->label(),
            ])
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
      ->action(function (Collection $records, array $data, Action $action) use ($channel, $successLabel, $afterBulk): void {
        $messageId = self::selectedMessageId($data);
        $eligible = $records->filter(function ($record) use ($channel): bool {
          if (! $record instanceof Invitation) {
            return false;
          }

          return match ($channel) {
            InvitationDispatchChannel::Email => filled($record->email),
            default => filled($record->phone),
          };
        });

        $result = app(InvitationDispatchService::class)->sendBulk($eligible, $channel, $messageId);

        if ($afterBulk !== null) {
          $afterBulk($result, $action);
        }

        Notification::make()
          ->title($result['sent'].' '.$successLabel.' ('.$result['failed'].' échec(s))')
          ->success()
          ->send();
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
        ->native(false);
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
