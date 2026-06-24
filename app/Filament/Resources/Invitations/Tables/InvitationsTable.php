<?php

namespace App\Filament\Resources\Invitations\Tables;

use App\Enums\InvitationRsvpStatus;
use App\Filament\Support\InvitationAdminActions;
use App\Models\Event;
use App\Models\Invitation;
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
          ->relationship('event', 'title')
          ->visible($eventContext === null),
        SelectFilter::make('organization')
          ->label('Type d\'invité')
          ->options(fn (): array => self::guestTypeFilterOptions($eventContext))
          ->searchable(),
        TernaryFilter::make('has_email')
          ->label('A un email')
          ->nullable()
          ->queries(
            true: fn ($query) => $query->whereNotNull('email')->where('email', '!=', ''),
            false: fn ($query) => $query->where(function ($builder): void {
              $builder
                ->whereNull('email')
                ->orWhere('email', '');
            }),
          ),
        TernaryFilter::make('has_phone')
          ->label('A un téléphone')
          ->nullable()
          ->queries(
            true: fn ($query) => $query->whereNotNull('phone')->where('phone', '!=', ''),
            false: fn ($query) => $query->where(function ($builder): void {
              $builder
                ->whereNull('phone')
                ->orWhere('phone', '');
            }),
          ),
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
        TernaryFilter::make('email_sent')
          ->label('Email envoyé')
          ->nullable()
          ->queries(
            true: fn ($query) => $query->whereNotNull('email_sent_at'),
            false: fn ($query) => $query->whereNull('email_sent_at'),
          ),
        TernaryFilter::make('sms_sent')
          ->label('SMS envoyé')
          ->nullable()
          ->queries(
            true: fn ($query) => $query->whereNotNull('sms_sent_at'),
            false: fn ($query) => $query->whereNull('sms_sent_at'),
          ),
        TernaryFilter::make('whatsapp_sent')
          ->label('WhatsApp envoyé')
          ->nullable()
          ->queries(
            true: fn ($query) => $query->whereNotNull('whatsapp_sent_at'),
            false: fn ($query) => $query->whereNull('whatsapp_sent_at'),
          ),
        TernaryFilter::make('any_invitation_sent')
          ->label('Invitation envoyée (au moins un canal)')
          ->nullable()
          ->queries(
            true: fn ($query) => $query->where(function ($builder): void {
              $builder
                ->whereNotNull('email_sent_at')
                ->orWhereNotNull('sms_sent_at')
                ->orWhereNotNull('whatsapp_sent_at');
            }),
            false: fn ($query) => $query->whereNull('email_sent_at')
              ->whereNull('sms_sent_at')
              ->whereNull('whatsapp_sent_at'),
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

  /**
   * Retourne les types d'invités distincts pour le filtre du tableau.
   *
   * @param Event|null $eventContext Événement parent si le tableau est contextualisé
   * @return array<string, string> Options valeur => libellé
   */
  private static function guestTypeFilterOptions(?Event $eventContext): array
  {
    $query = Invitation::query()
      ->whereNotNull('organization')
      ->where('organization', '!=', '');

    if ($eventContext !== null) {
      $query->where('event_id', $eventContext->id);
    }

    return $query
      ->orderBy('organization')
      ->distinct()
      ->pluck('organization', 'organization')
      ->all();
  }
}
