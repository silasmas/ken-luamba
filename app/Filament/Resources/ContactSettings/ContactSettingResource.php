<?php

namespace App\Filament\Resources\ContactSettings;

use App\Filament\Resources\ContactSettings\Pages\ListContactSettings;
use App\Filament\Resources\ContactSettings\Pages\ManageContactSettings;
use App\Filament\Resources\ContactSettings\Schemas\ContactSettingForm;
use App\Models\ContactSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ContactSettingResource extends Resource
{
  protected static ?string $model = ContactSetting::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

  protected static ?string $navigationLabel = 'Page contact';

  protected static ?string $modelLabel = 'Page contact';

  protected static ?string $pluralModelLabel = 'Page contact';

  protected static string|UnitEnum|null $navigationGroup = 'Site web';

  protected static ?int $navigationSort = 2;

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
    return ContactSettingForm::configure($schema);
  }

  public static function getRelations(): array
  {
    return [];
  }

  public static function getPages(): array
  {
    return [
      'index' => ListContactSettings::route('/'),
      'edit' => ManageContactSettings::route('/{record}/edit'),
    ];
  }

  /**
   * Retourne l'URL de navigation vers les paramètres singleton.
   *
   * @return string URL d'édition
   */
  public static function getNavigationUrl(): string
  {
    $record = ContactSetting::instance();

    return static::getUrl('edit', ['record' => $record]);
  }
}
