<?php

namespace App\Filament\Resources\AdminAppearanceSettings;

use App\Filament\Resources\AdminAppearanceSettings\Pages\ListAdminAppearanceSettings;
use App\Filament\Resources\AdminAppearanceSettings\Pages\ManageAdminAppearanceSettings;
use App\Filament\Resources\AdminAppearanceSettings\Schemas\AdminAppearanceSettingForm;
use App\Models\AdminAppearanceSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Resource Filament pour les paramètres d'apparence de l'administration.
 */
class AdminAppearanceSettingResource extends Resource
{
  protected static ?string $model = AdminAppearanceSetting::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

  protected static ?string $navigationLabel = 'Apparence admin';

  protected static ?string $modelLabel = 'Apparence admin';

  protected static ?string $pluralModelLabel = 'Apparence admin';

  protected static string|UnitEnum|null $navigationGroup = 'Système';

  protected static ?int $navigationSort = 1;

  /**
   * Les paramètres sont gérés via une page unique.
   *
   * @return bool False pour masquer la création
   */
  public static function canCreate(): bool
  {
    return false;
  }

  /**
   * Configure le formulaire d'édition.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public static function form(Schema $schema): Schema
  {
    return AdminAppearanceSettingForm::configure($schema);
  }

  /**
   * @return array<int, mixed> Relations vides
   */
  public static function getRelations(): array
  {
    return [];
  }

  /**
   * @return array<string, mixed> Pages de la resource
   */
  public static function getPages(): array
  {
    return [
      'index' => ListAdminAppearanceSettings::route('/'),
      'edit' => ManageAdminAppearanceSettings::route('/{record}/edit'),
    ];
  }

  /**
   * Retourne l'URL de navigation vers les paramètres singleton.
   *
   * @return string URL d'édition
   */
  public static function getNavigationUrl(): string
  {
    $record = AdminAppearanceSetting::instance();

    return static::getUrl('edit', ['record' => $record]);
  }
}
