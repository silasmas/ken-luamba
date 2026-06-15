<?php

namespace App\Filament\Resources\ShopSettings;

use App\Filament\Resources\ShopSettings\Pages\ListShopSettings;
use App\Filament\Resources\ShopSettings\Pages\ManageShopSettings;
use App\Filament\Resources\ShopSettings\Schemas\ShopSettingForm;
use App\Models\ShopSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ShopSettingResource extends Resource
{
  protected static ?string $model = ShopSetting::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

  protected static ?string $navigationLabel = 'Paramètres boutique';

  protected static ?string $modelLabel = 'Paramètres boutique';

  protected static ?string $pluralModelLabel = 'Paramètres boutique';

  protected static string|UnitEnum|null $navigationGroup = 'Ventes';

  protected static ?int $navigationSort = 5;

  /**
   * Les paramètres sont gérés via une page unique.
   *
   * @return bool False pour masquer la création
   */
  public static function canCreate(): bool
  {
    return false;
  }

  public static function form(Schema $schema): Schema
  {
    return ShopSettingForm::configure($schema);
  }

  public static function getRelations(): array
  {
    return [];
  }

  public static function getPages(): array
  {
    return [
      'index' => ListShopSettings::route('/'),
      'edit' => ManageShopSettings::route('/{record}/edit'),
    ];
  }

  /**
   * Retourne l'URL de navigation vers les paramètres singleton.
   *
   * @return string URL d'édition
   */
  public static function getNavigationUrl(): string
  {
    $record = ShopSetting::instance();

    return static::getUrl('edit', ['record' => $record]);
  }
}
