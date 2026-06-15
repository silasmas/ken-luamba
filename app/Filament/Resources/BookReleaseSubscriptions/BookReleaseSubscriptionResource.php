<?php

namespace App\Filament\Resources\BookReleaseSubscriptions;

use App\Filament\Resources\BookReleaseSubscriptions\Pages\ListBookReleaseSubscriptions;
use App\Filament\Resources\BookReleaseSubscriptions\Tables\BookReleaseSubscriptionsTable;
use App\Models\BookReleaseSubscription;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Ressource Filament listant les inscriptions « être prévenu de la sortie ».
 */
class BookReleaseSubscriptionResource extends Resource
{
  protected static ?string $model = BookReleaseSubscription::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

  protected static ?string $navigationLabel = 'Alertes sortie';

  protected static ?string $modelLabel = 'Alerte sortie';

  protected static ?string $pluralModelLabel = 'Alertes sortie';

  protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

  protected static ?int $navigationSort = 4;

  /**
   * Désactive le formulaire d'édition (lecture seule).
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma vide
   */
  public static function form(Schema $schema): Schema
  {
    return $schema;
  }

  /**
   * Configure le tableau des inscriptions e-mail.
   *
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public static function table(Table $table): Table
  {
    return BookReleaseSubscriptionsTable::configure($table);
  }

  /**
   * @return array<int, string>
   */
  public static function getRelations(): array
  {
    return [];
  }

  /**
   * @return array<string, string>
   */
  public static function getPages(): array
  {
    return [
      'index' => ListBookReleaseSubscriptions::route('/'),
    ];
  }

  /**
   * Les inscriptions sont créées côté site public uniquement.
   */
  public static function canCreate(): bool
  {
    return false;
  }
}
