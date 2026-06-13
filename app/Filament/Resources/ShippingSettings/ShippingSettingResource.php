<?php

namespace App\Filament\Resources\ShippingSettings;

use App\Filament\Resources\ShippingSettings\Pages\ListShippingSettings;
use App\Filament\Resources\ShippingSettings\Pages\ManageShippingSettings;
use App\Filament\Resources\ShippingSettings\Schemas\ShippingSettingForm;
use App\Models\ShippingSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ShippingSettingResource extends Resource
{
  protected static ?string $model = ShippingSetting::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

  protected static ?string $navigationLabel = 'Paramètres livraison';

  protected static ?string $modelLabel = 'Paramètres livraison';

  protected static ?string $pluralModelLabel = 'Paramètres livraison';

  protected static string|UnitEnum|null $navigationGroup = 'Ventes';

  protected static ?int $navigationSort = 6;

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
    return ShippingSettingForm::configure($schema);
  }

  public static function getRelations(): array
  {
    return [];
  }

  public static function getPages(): array
  {
    return [
      'index' => ListShippingSettings::route('/'),
      'edit' => ManageShippingSettings::route('/{record}/edit'),
    ];
  }

  /**
   * Retourne l'URL de navigation vers les paramètres singleton.
   *
   * @return string URL d'édition
   */
  public static function getNavigationUrl(): string
  {
    $record = ShippingSetting::instance();

    return static::getUrl('edit', ['record' => $record]);
  }
}
