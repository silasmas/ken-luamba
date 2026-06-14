<?php

namespace App\Filament\Resources\Events\RelationManagers;

use App\Enums\InvitationDispatchChannel;
use App\Filament\Resources\Invitations\Tables\InvitationsTable;
use App\Filament\Support\AdminFormLayout;
use App\Filament\Support\InvitationAdminActions;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class InvitationsRelationManager extends RelationManager
{
  protected static string $relationship = 'invitations';

  protected static ?string $title = 'Invités';

  /**
   * Configure le formulaire d'une invitation liée à l'événement.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public function form(Schema $schema): Schema
  {
    return AdminFormLayout::fullWidth($schema)
      ->components([
        AdminFormLayout::section(
          'Invité',
          'Ajoutez un invité à cet événement.',
          [
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
            TextInput::make('organization')
              ->label('Organisation')
              ->maxLength(255),
            Textarea::make('admin_notes')
              ->label('Notes internes')
              ->rows(2)
              ->columnSpanFull(),
          ],
          2,
        ),
      ]);
  }

  /**
   * Configure le tableau des invitations de l'événement.
   *
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public function table(Table $table): Table
  {
    return InvitationsTable::configure($table)
      ->headerActions([
        CreateAction::make(),
        ActionGroup::make([
          InvitationAdminActions::sendAllForEvent(
            InvitationDispatchChannel::Email,
            fn () => $this->getOwnerRecord()->invitations()->whereNotNull('email')->get(),
          ),
          InvitationAdminActions::sendAllForEvent(
            InvitationDispatchChannel::Sms,
            fn () => $this->getOwnerRecord()->invitations()->whereNotNull('phone')->get(),
          ),
          InvitationAdminActions::sendAllForEvent(
            InvitationDispatchChannel::Whatsapp,
            fn () => $this->getOwnerRecord()->invitations()->whereNotNull('phone')->get(),
          ),
        ])->label('Envoyer à tous'),
      ]);
  }
}
