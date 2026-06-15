<?php

namespace App\Filament\Resources\QuantityDiscounts\Schemas;

use App\Enums\DiscountAppliesTo;
use App\Enums\DiscountType;
use App\Filament\Support\AdminFormLayout;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class QuantityDiscountForm
{
  /**
   * Configure le formulaire d'une remise par quantité.
   *
   * @param Schema $schema Schéma Filament à compléter
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    return AdminFormLayout::fullWidth($schema)
      ->components([
        AdminFormLayout::section(
          'Règle de remise',
          'Définissez quand et comment la remise s\'applique.',
          [
            TextInput::make('name')
              ->label('Nom de la remise')
              ->required()
              ->maxLength(255)
              ->helperText('Libellé interne (ex. Pack 3 livres -10%).'),
            TextInput::make('min_quantity')
              ->label('Quantité minimale')
              ->required()
              ->numeric()
              ->minValue(2)
              ->helperText('Nombre minimum d\'articles pour déclencher la remise.'),
            Select::make('discount_type')
              ->label('Type de remise')
              ->options(collect(DiscountType::cases())->mapWithKeys(
                fn (DiscountType $type): array => [$type->value => $type->label()]
              )->all())
              ->required()
              ->native(false)
              ->helperText('Pourcentage ou montant fixe.'),
            TextInput::make('discount_value')
              ->label('Valeur')
              ->required()
              ->numeric()
              ->minValue(0)
              ->helperText('Ex. 10 pour 10 % ou 5000 pour une remise fixe (même devise que le panier).'),
          ],
        ),
        AdminFormLayout::section(
          'Périmètre & validité',
          'Articles concernés et période d\'application.',
          [
            Select::make('applies_to')
              ->label('S\'applique à')
              ->options(collect(DiscountAppliesTo::cases())->mapWithKeys(
                fn (DiscountAppliesTo $scope): array => [$scope->value => $scope->label()]
              )->all())
              ->required()
              ->live()
              ->native(false)
              ->helperText('Tous les livres, physiques seulement ou un livre précis.'),
            Select::make('book_id')
              ->label('Livre ciblé')
              ->relationship('book', 'title')
              ->searchable()
              ->preload()
              ->visible(fn (callable $get): bool => $get('applies_to') === DiscountAppliesTo::SpecificBook->value)
              ->helperText('Obligatoire si la remise cible un livre spécifique.'),
            Toggle::make('stackable')
              ->label('Cumulable avec d\'autres promos')
              ->helperText('Autorise la combinaison avec d\'autres remises.'),
            DateTimePicker::make('valid_from')
              ->label('Valide à partir de')
              ->helperText('Laisser vide = immédiat.'),
            DateTimePicker::make('valid_until')
              ->label('Valide jusqu\'au')
              ->helperText('Laisser vide = sans date de fin.'),
            Toggle::make('is_active')
              ->label('Active')
              ->default(true)
              ->helperText('Désactivez pour mettre en pause la règle.'),
          ],
        ),
      ]);
  }
}
