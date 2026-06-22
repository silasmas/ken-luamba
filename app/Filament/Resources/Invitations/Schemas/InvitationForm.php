<?php

namespace App\Filament\Resources\Invitations\Schemas;

use App\Enums\InvitationRsvpStatus;
use App\Filament\Support\AdminFormLayout;
use App\Filament\Support\InvitationGuestTypeField;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InvitationForm
{
  /**
   * Configure le formulaire d'une invitation.
   *
   * @param Schema $schema Schéma Filament à compléter
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    return AdminFormLayout::fullWidth($schema)
      ->components([
        AdminFormLayout::section(
          'Invité',
          'Coordonnées de la personne invitée à l\'événement.',
          [
            Select::make('event_id')
              ->label('Événement')
              ->relationship('event', 'title')
              ->searchable()
              ->preload()
              ->required(),
            TextInput::make('full_name')
              ->label('Nom complet')
              ->required()
              ->maxLength(255),
            TextInput::make('email')
              ->label('Email')
              ->email()
              ->maxLength(255),
            TextInput::make('phone')
              ->label('Téléphone / WhatsApp')
              ->tel()
              ->maxLength(30),
            InvitationGuestTypeField::make(),
            Select::make('rsvp_status')
              ->label('Réponse RSVP')
              ->options(collect(InvitationRsvpStatus::cases())->mapWithKeys(
                fn (InvitationRsvpStatus $status) => [$status->value => $status->label()],
              )->all())
              ->disabled()
              ->dehydrated(false),
            Textarea::make('guest_message')
              ->label('Message de l\'invité')
              ->rows(3)
              ->disabled()
              ->dehydrated(false)
              ->columnSpanFull(),
            Textarea::make('admin_notes')
              ->label('Notes internes')
              ->rows(3)
              ->columnSpanFull(),
          ],
          2,
        ),
      ]);
  }
}
