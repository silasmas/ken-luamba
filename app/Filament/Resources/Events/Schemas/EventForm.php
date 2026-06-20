<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Enums\EventType;
use App\Enums\InvitationDispatchChannel;
use App\Models\Event;
use App\Filament\Support\AdminFormLayout;
use App\Filament\Support\InvitationPlaceholderHelper;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class EventForm
{
  /**
   * Configure le formulaire d'un événement.
   *
   * @param Schema $schema Schéma Filament à compléter
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    return AdminFormLayout::fullWidth($schema)
      ->components([
        AdminFormLayout::section(
          'Événement',
          'Cérémonie de publication, lancement de livre ou autre rassemblement.',
          [
            TextInput::make('title')
              ->label('Titre')
              ->required()
              ->maxLength(255),
            TextInput::make('slug')
              ->label('Slug')
              ->maxLength(255)
              ->helperText('Généré automatiquement si vide.'),
            Select::make('type')
              ->label('Type')
              ->options(collect(EventType::cases())->mapWithKeys(
                fn (EventType $type) => [$type->value => $type->label()],
              )->all())
              ->required()
              ->default(EventType::BookLaunch->value),
            Select::make('books')
              ->label('Livres associés')
              ->relationship('books', 'title')
              ->multiple()
              ->searchable()
              ->preload(),
            DateTimePicker::make('starts_at')
              ->label('Date de début')
              ->required()
              ->seconds(false),
            DateTimePicker::make('ends_at')
              ->label('Date de fin')
              ->seconds(false),
            TextInput::make('location')
              ->label('Lieu')
              ->maxLength(255)
              ->columnSpanFull(),
            Textarea::make('venue_details')
              ->label('Détails du lieu')
              ->rows(2)
              ->columnSpanFull(),
            Textarea::make('description')
              ->label('Description')
              ->rows(4)
              ->columnSpanFull(),
            Textarea::make('welcome_message')
              ->label('Message d\'accueil (page invitation)')
              ->rows(3)
              ->columnSpanFull()
              ->helperText('Affiché sur la page publique de réponse.'),
            Toggle::make('is_published')
              ->label('Publié')
              ->default(true),
          ],
          2,
        ),
        AdminFormLayout::section(
          'Messages d\'invitation',
          'Créez plusieurs modèles réutilisables pour les envois email, SMS et WhatsApp.',
          [
            Placeholder::make('invitation_variables')
              ->label('Variables disponibles')
              ->content(fn () => InvitationPlaceholderHelper::toHtml())
              ->columnSpanFull(),
            Repeater::make('invitation_messages')
              ->label('Modèles')
              ->schema([
                Hidden::make('id')
                  ->default(fn (): string => (string) Str::uuid()),
                TextInput::make('label')
                  ->label('Nom du modèle')
                  ->required()
                  ->maxLength(120),
                CheckboxList::make('channels')
                  ->label('Canaux')
                  ->options(collect(InvitationDispatchChannel::cases())->mapWithKeys(
                    fn (InvitationDispatchChannel $channel) => [$channel->value => $channel->label()],
                  )->all())
                  ->required()
                  ->columns(3),
                TextInput::make('email_subject')
                  ->label('Objet email')
                  ->maxLength(255)
                  ->helperText('Utilisé uniquement pour les envois email.'),
                Textarea::make('body')
                  ->label('Contenu du message')
                  ->required()
                  ->rows(8)
                  ->columnSpanFull(),
              ])
              ->columns(2)
              ->collapsible()
              ->itemLabel(fn (array $state): string => $state['label'] ?? 'Modèle')
              ->addActionLabel('Ajouter un modèle')
              ->columnSpanFull(),
          ],
          1,
        ),
        AdminFormLayout::section(
          'Rappels programmés',
          'Envoi automatique aux invités qui n\'ont pas encore reçu le message sur le canal choisi.',
          [
            Toggle::make('invitation_auto_send_enabled')
              ->label('Envoi programmé activé')
              ->live()
              ->helperText('Nécessite que la tâche planifiée Laravel tourne (cron schedule:run).'),
            DateTimePicker::make('invitation_auto_send_at')
              ->label('Date et heure d\'envoi')
              ->seconds(false)
              ->visible(fn (callable $get): bool => (bool) $get('invitation_auto_send_enabled'))
              ->helperText('Les invités éligibles recevront le rappel à cette date.'),
            Select::make('invitation_auto_send_channel')
              ->label('Canal')
              ->options(fn (?Event $record): array => app(\App\Services\Invitations\InvitationMessageService::class)
                ->enabledChannelOptions($record))
              ->default(InvitationDispatchChannel::Email->value)
              ->native(false)
              ->visible(fn (callable $get): bool => (bool) $get('invitation_auto_send_enabled'))
              ->helperText('Seuls les canaux activés dans vos modèles de message sont proposés.'),
            Select::make('invitation_auto_send_message_id')
              ->label('Modèle de message')
              ->options(function (?Event $record, callable $get): array {
                $channelValue = $get('invitation_auto_send_channel') ?? InvitationDispatchChannel::Email->value;
                $channel = InvitationDispatchChannel::tryFrom((string) $channelValue) ?? InvitationDispatchChannel::Email;

                return app(\App\Services\Invitations\InvitationMessageService::class)
                  ->optionsForChannel($record, $channel);
              })
              ->native(false)
              ->visible(fn (callable $get): bool => (bool) $get('invitation_auto_send_enabled')),
          ],
          1,
        ),
      ]);
  }
}
