<?php

namespace App\Filament\Resources\Deliveries;

use App\Filament\Resources\Deliveries\Pages\EditDelivery;
use App\Filament\Resources\Deliveries\Pages\ListDeliveries;
use App\Filament\Resources\Deliveries\Schemas\DeliveryForm;
use App\Filament\Resources\Deliveries\Tables\DeliveriesTable;
use App\Models\Delivery;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DeliveryResource extends Resource
{
  protected static ?string $model = Delivery::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

  protected static ?string $navigationLabel = 'Livraisons';

  protected static ?string $modelLabel = 'Livraison';

  protected static ?string $pluralModelLabel = 'Livraisons';

  protected static string|UnitEnum|null $navigationGroup = 'Ventes';

  protected static ?int $navigationSort = 4;

  /**
   * Les livraisons sont créées automatiquement après paiement.
   *
   * @return bool False pour masquer la création manuelle
   */
  public static function canCreate(): bool
  {
    return false;
  }

  public static function form(Schema $schema): Schema
  {
    return DeliveryForm::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return DeliveriesTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [];
  }

  public static function getPages(): array
  {
    return [
      'index' => ListDeliveries::route('/'),
      'edit' => EditDelivery::route('/{record}/edit'),
    ];
  }
}
