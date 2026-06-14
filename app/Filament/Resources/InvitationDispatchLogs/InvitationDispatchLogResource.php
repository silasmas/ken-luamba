<?php

namespace App\Filament\Resources\InvitationDispatchLogs;

use App\Filament\Resources\InvitationDispatchLogs\Pages\ListInvitationDispatchLogs;
use App\Filament\Resources\InvitationDispatchLogs\Tables\InvitationDispatchLogsTable;
use App\Models\InvitationDispatchLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class InvitationDispatchLogResource extends Resource
{
  protected static ?string $model = InvitationDispatchLog::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

  protected static ?string $navigationLabel = 'Historique envois';

  protected static ?string $modelLabel = 'Envoi';

  protected static ?string $pluralModelLabel = 'Historique des envois';

  protected static string|UnitEnum|null $navigationGroup = 'Événements';

  protected static ?int $navigationSort = 3;

  public static function form(Schema $schema): Schema
  {
    return $schema;
  }

  public static function table(Table $table): Table
  {
    return InvitationDispatchLogsTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [];
  }

  public static function getPages(): array
  {
    return [
      'index' => ListInvitationDispatchLogs::route('/'),
    ];
  }

  public static function canCreate(): bool
  {
    return false;
  }
}
