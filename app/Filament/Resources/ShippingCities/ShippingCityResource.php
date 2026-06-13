<?php

namespace App\Filament\Resources\ShippingCities;

use App\Filament\Resources\ShippingCities\Pages\CreateShippingCity;
use App\Filament\Resources\ShippingCities\Pages\EditShippingCity;
use App\Filament\Resources\ShippingCities\Pages\ListShippingCities;
use App\Filament\Resources\ShippingCities\RelationManagers\ZonesRelationManager;
use App\Filament\Resources\ShippingCities\Schemas\ShippingCityForm;
use App\Filament\Resources\ShippingCities\Tables\ShippingCitiesTable;
use App\Models\ShippingCity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ShippingCityResource extends Resource
{
  protected static ?string $model = ShippingCity::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

  protected static ?string $navigationLabel = 'Villes de livraison';

  protected static ?string $modelLabel = 'Ville de livraison';

  protected static ?string $pluralModelLabel = 'Villes de livraison';

  protected static string|UnitEnum|null $navigationGroup = 'Ventes';

  protected static ?int $navigationSort = 5;

  public static function form(Schema $schema): Schema
  {
    return ShippingCityForm::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return ShippingCitiesTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [
      ZonesRelationManager::class,
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => ListShippingCities::route('/'),
      'create' => CreateShippingCity::route('/create'),
      'edit' => EditShippingCity::route('/{record}/edit'),
    ];
  }
}
