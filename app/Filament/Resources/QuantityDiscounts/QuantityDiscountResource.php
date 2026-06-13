<?php

namespace App\Filament\Resources\QuantityDiscounts;

use App\Filament\Resources\QuantityDiscounts\Pages\CreateQuantityDiscount;
use App\Filament\Resources\QuantityDiscounts\Pages\EditQuantityDiscount;
use App\Filament\Resources\QuantityDiscounts\Pages\ListQuantityDiscounts;
use App\Filament\Resources\QuantityDiscounts\Schemas\QuantityDiscountForm;
use App\Filament\Resources\QuantityDiscounts\Tables\QuantityDiscountsTable;
use App\Models\QuantityDiscount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class QuantityDiscountResource extends Resource
{
  protected static ?string $model = QuantityDiscount::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

  protected static ?string $navigationLabel = 'Remises par quantité';

  protected static ?string $modelLabel = 'Remise';

  protected static ?string $pluralModelLabel = 'Remises par quantité';

  protected static string|UnitEnum|null $navigationGroup = 'Tarification';

  protected static ?int $navigationSort = 2;

  public static function form(Schema $schema): Schema
  {
    return QuantityDiscountForm::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return QuantityDiscountsTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [];
  }

  public static function getPages(): array
  {
    return [
      'index' => ListQuantityDiscounts::route('/'),
      'create' => CreateQuantityDiscount::route('/create'),
      'edit' => EditQuantityDiscount::route('/{record}/edit'),
    ];
  }
}
