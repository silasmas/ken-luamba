<?php

namespace App\Filament\Resources\InvitationDispatchLogs\Tables;

use App\Enums\InvitationDispatchChannel;
use App\Enums\InvitationDispatchStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvitationDispatchLogsTable
{
  /**
   * Configure le tableau de l'historique des envois.
   *
   * @param Table $table Table Filament à configurer
   * @return Table Table configurée
   */
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('sent_at')
          ->label('Date')
          ->dateTime('d/m/Y H:i')
          ->sortable(),
        TextColumn::make('channel')
          ->label('Canal')
          ->badge()
          ->formatStateUsing(fn ($state) => $state?->label()),
        TextColumn::make('status')
          ->label('Statut')
          ->badge()
          ->color(fn ($state) => $state === InvitationDispatchStatus::Sent ? 'success' : 'danger')
          ->formatStateUsing(fn ($state) => $state?->label()),
        TextColumn::make('recipient_name')
          ->label('Invité')
          ->searchable(),
        TextColumn::make('recipient')
          ->label('Destinataire')
          ->searchable()
          ->toggleable(),
        TextColumn::make('event.title')
          ->label('Événement')
          ->searchable()
          ->toggleable(),
        TextColumn::make('message_body')
          ->label('Message')
          ->limit(60)
          ->tooltip(fn ($record) => $record->message_body),
        TextColumn::make('provider_response')
          ->label('Réponse API')
          ->limit(40)
          ->placeholder('—')
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('sender.name')
          ->label('Envoyé par')
          ->placeholder('—')
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        SelectFilter::make('channel')
          ->label('Canal')
          ->options(collect(InvitationDispatchChannel::cases())->mapWithKeys(
            fn (InvitationDispatchChannel $channel) => [$channel->value => $channel->label()],
          )->all()),
        SelectFilter::make('status')
          ->label('Statut')
          ->options(collect(InvitationDispatchStatus::cases())->mapWithKeys(
            fn (InvitationDispatchStatus $status) => [$status->value => $status->label()],
          )->all()),
        SelectFilter::make('event_id')
          ->label('Événement')
          ->relationship('event', 'title'),
      ])
      ->defaultSort('sent_at', 'desc');
  }
}
