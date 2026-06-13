<?php

namespace App\Filament\Resources\ShippingZones;

use App\Filament\Resources\ShippingZones\Pages\CreateShippingZone;
use App\Filament\Resources\ShippingZones\Pages\EditShippingZone;
use App\Filament\Resources\ShippingZones\Pages\ListShippingZones;
use App\Filament\Resources\ShippingZones\RelationManagers\CommunesRelationManager;
use App\Filament\Resources\ShippingZones\Schemas\ShippingZoneForm;
use App\Filament\Resources\ShippingZones\Tables\ShippingZonesTable;
use App\Models\ShippingZone;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ShippingZoneResource extends Resource
{
  protected static ?string $model = ShippingZone::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

  protected static ?string $navigationLabel = 'Zones de livraison';

  protected static ?string $modelLabel = 'Zone de livraison';

  protected static ?string $pluralModelLabel = 'Zones de livraison';

  protected static string|UnitEnum|null $navigationGroup = 'Ventes';

  protected static ?int $navigationSort = 7;

  public static function form(Schema $schema): Schema
  {
    return ShippingZoneForm::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return ShippingZonesTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [
      CommunesRelationManager::class,
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => ListShippingZones::route('/'),
      'create' => CreateShippingZone::route('/create'),
      'edit' => EditShippingZone::route('/{record}/edit'),
    ];
  }
}
