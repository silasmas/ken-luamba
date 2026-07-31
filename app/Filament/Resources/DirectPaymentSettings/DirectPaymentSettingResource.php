<?php

namespace App\Filament\Resources\DirectPaymentSettings;

use App\Filament\Resources\DirectPaymentSettings\Pages\ListDirectPaymentSettings;
use App\Filament\Resources\DirectPaymentSettings\Pages\ManageDirectPaymentSettings;
use App\Filament\Resources\DirectPaymentSettings\Schemas\DirectPaymentSettingForm;
use App\Models\DirectPaymentSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Ressource Filament des paramètres paiement direct (singleton).
 */
class DirectPaymentSettingResource extends Resource
{
  protected static ?string $model = DirectPaymentSetting::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

  protected static ?string $navigationLabel = 'Paiement direct';

  protected static ?string $modelLabel = 'Paiement direct';

  protected static ?string $pluralModelLabel = 'Paiement direct';

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

  /**
   * Configure le formulaire.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public static function form(Schema $schema): Schema
  {
    return DirectPaymentSettingForm::configure($schema);
  }

  /**
   * Relations Filament.
   *
   * @return array<int, mixed>
   */
  public static function getRelations(): array
  {
    return [];
  }

  /**
   * Pages de la ressource.
   *
   * @return array<string, mixed>
   */
  public static function getPages(): array
  {
    return [
      'index' => ListDirectPaymentSettings::route('/'),
      'edit' => ManageDirectPaymentSettings::route('/{record}/edit'),
    ];
  }

  /**
   * Retourne l'URL de navigation vers les paramètres singleton.
   *
   * @return string URL d'édition
   */
  public static function getNavigationUrl(): string
  {
    $record = DirectPaymentSetting::instance();

    return static::getUrl('edit', ['record' => $record]);
  }
}
