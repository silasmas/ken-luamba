<?php

namespace App\Filament\Resources\Invitations\Tables;

use App\Enums\InvitationRsvpStatus;
use App\Filament\Support\InvitationAdminActions;
use App\Models\Event;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class InvitationsTable
{
  /**
   * Configure le tableau de liste des invitations.
   *
   * @param Table $table Table Filament à configurer
   * @param Event|null $eventContext Événement parent pour filtrer les canaux d'envoi
   * @return Table Table configurée
   */
  public static function configure(Table $table, ?Event $eventContext = null): Table
  {
    return $table
      ->columns([
        TextColumn::make('event.title')
          ->label('Événement')
          ->searchable()
          ->sortable(),
        TextColumn::make('full_name')
          ->label('Invité')
          ->searchable()
          ->sortable(),
        TextColumn::make('email')
          ->label('Email')
          ->toggleable(),
        TextColumn::make('phone')
          ->label('Téléphone')
          ->toggleable(),
        TextColumn::make('organization')
          ->label('Type d\'invité')
          ->searchable()
          ->badge()
          ->color('gray')
          ->placeholder('—')
          ->toggleable(),
        TextColumn::make('rsvp_status')
          ->label('RSVP')
          ->badge()
          ->color(fn ($state) => match ($state) {
            InvitationRsvpStatus::Attending => 'success',
            InvitationRsvpStatus::NotAttending => 'danger',
            default => 'gray',
          })
          ->formatStateUsing(fn ($state) => $state?->label()),
        TextColumn::make('guest_message')
          ->label('Commentaire')
          ->limit(40)
          ->tooltip(fn (?string $state): ?string => filled($state) ? $state : null)
          ->placeholder('—')
          ->toggleable(),
        TextColumn::make('email_sent_at')
          ->label('Email')
          ->dateTime('d/m/Y H:i')
          ->placeholder('—')
          ->toggleable(),
        TextColumn::make('whatsapp_sent_at')
          ->label('WhatsApp')
          ->dateTime('d/m/Y H:i')
          ->placeholder('—')
          ->toggleable(),
        TextColumn::make('sms_sent_at')
          ->label('SMS')
          ->dateTime('d/m/Y H:i')
          ->placeholder('—')
          ->toggleable(),
        TextColumn::make('responded_at')
          ->label('Répondu le')
          ->dateTime('d/m/Y H:i')
          ->placeholder('—')
          ->toggleable(),
      ])
      ->filters([
        SelectFilter::make('event_id')
          ->label('Événement')
          ->relationship('event', 'title'),
        SelectFilter::make('rsvp_status')
          ->label('RSVP')
          ->options(collect(InvitationRsvpStatus::cases())->mapWithKeys(
            fn (InvitationRsvpStatus $status) => [$status->value => $status->label()],
          )->all()),
        TernaryFilter::make('has_response')
          ->label('A répondu')
          ->nullable()
          ->queries(
            true: fn ($query) => $query->whereNotNull('responded_at'),
            false: fn ($query) => $query->whereNull('responded_at'),
          ),
      ])
      ->recordActions([
        InvitationAdminActions::viewRsvpResponse(),
        InvitationAdminActions::sendEmail(),
        InvitationAdminActions::openWhatsapp(),
        InvitationAdminActions::openSms(),
        InvitationAdminActions::copyLink(),
        EditAction::make(),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          ...InvitationAdminActions::bulkActionsForEvent($eventContext),
          DeleteBulkAction::make(),
        ]),
      ])
      ->defaultSort('created_at', 'desc');
  }
}
