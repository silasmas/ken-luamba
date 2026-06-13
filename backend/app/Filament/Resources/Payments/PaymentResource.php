<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Resources\Payments\Pages\EditPayment;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Schemas\PaymentForm;
use App\Filament\Resources\Payments\Tables\PaymentsTable;
use App\Models\Payment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PaymentResource extends Resource
{
  protected static ?string $model = Payment::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

  protected static ?string $navigationLabel = 'Paiements';

  protected static ?string $modelLabel = 'Paiement';

  protected static ?string $pluralModelLabel = 'Paiements';

  protected static string|UnitEnum|null $navigationGroup = 'Ventes';

  protected static ?int $navigationSort = 2;

  /**
   * Les paiements sont créés automatiquement avec les commandes.
   *
   * @return bool False pour masquer la création manuelle
   */
  public static function canCreate(): bool
  {
    return false;
  }

  public static function form(Schema $schema): Schema
  {
    return PaymentForm::configure($schema);
  }

  public static function table(Table $table): Table
  {
    return PaymentsTable::configure($table);
  }

  public static function getRelations(): array
  {
    return [];
  }

  public static function getPages(): array
  {
    return [
      'index' => ListPayments::route('/'),
      'edit' => EditPayment::route('/{record}/edit'),
    ];
  }
}
