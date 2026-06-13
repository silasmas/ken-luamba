<?php

namespace App\Filament\Resources\PickupPoints;

use App\Filament\Resources\PickupPoints\Pages\CreatePickupPoint;
use App\Filament\Resources\PickupPoints\Pages\EditPickupPoint;
use App\Filament\Resources\PickupPoints\Pages\ListPickupPoints;
use App\Filament\Resources\PickupPoints\Schemas\PickupPointForm;
use App\Filament\Resources\PickupPoints\Tables\PickupPointsTable;
use App\Models\PickupPoint;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PickupPointResource extends Resource
{
  protected static ?string $model = PickupPoint::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

  protected static ?string $navigationLabel = 'Points de retrait';

  protected static ?string $modelLabel = 'Point de retrait';

  protected static ?string $pluralModelLabel = 'Points de retrait';

  protected static string|UnitEnum|null $navigationGroup = 'Ventes';

  protected static ?int $navigationSort = 3;

  public static function form(Schema $schema): Schema
  {
    return PickupPointForm::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return PickupPointsTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [];
  }

  public static function getPages(): array
  {
    return [
      'index' => ListPickupPoints::route('/'),
      'create' => CreatePickupPoint::route('/create'),
      'edit' => EditPickupPoint::route('/{record}/edit'),
    ];
  }
}
