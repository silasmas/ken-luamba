<?php

namespace App\Filament\Resources\QuantityDiscounts\Schemas;

use App\Enums\DiscountAppliesTo;
use App\Enums\DiscountType;
use App\Filament\Support\AdminFormLayout;
use App\Services\DiscountService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
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
              ->required(fn (callable $get): bool => $get('applies_to') !== DiscountAppliesTo::AuthorCompleteSet->value)
              ->numeric()
              ->minValue(2)
              ->default(2)
              ->hidden(fn (callable $get): bool => $get('applies_to') === DiscountAppliesTo::AuthorCompleteSet->value)
              ->dehydrated(fn (callable $get): bool => $get('applies_to') !== DiscountAppliesTo::AuthorCompleteSet->value)
              ->helperText(function (callable $get): string {
                return match ($get('applies_to')) {
                  DiscountAppliesTo::DistinctPhysicalBooks->value => 'Nombre minimum de titres physiques différents (ex. 4 = pack de 4 livres distincts).',
                  DiscountAppliesTo::PhysicalOnly->value => 'Nombre minimum d\'exemplaires physiques au total (ex. 4 = 4 brochés, même titre possible).',
                  DiscountAppliesTo::AllBooks->value => 'Nombre minimum d\'articles au total, y compris ebooks et audio.',
                  DiscountAppliesTo::SpecificBook->value => 'Nombre minimum d\'exemplaires du livre ciblé.',
                  default => 'Nombre minimum d\'articles pour déclencher la remise.',
                };
              }),
            Placeholder::make('quantity_total_notice')
              ->label('Comptage par quantité')
              ->content('La remise se déclenche dès que le nombre total d\'exemplaires atteint le seuil, y compris plusieurs copies du même livre.')
              ->visible(fn (callable $get): bool => in_array($get('applies_to'), [
                DiscountAppliesTo::PhysicalOnly->value,
                DiscountAppliesTo::AllBooks->value,
                DiscountAppliesTo::SpecificBook->value,
              ], true)),
            Placeholder::make('distinct_physical_books_notice')
              ->label('Comptage par titres distincts')
              ->content('Chaque livre physique compte une seule fois, même si plusieurs exemplaires du même titre sont dans le panier. Ex. : 4 titres différents avec 1 exemplaire chacun déclenche une remise à 4, mais 4 exemplaires du même livre ne la déclenche pas.')
              ->visible(fn (callable $get): bool => $get('applies_to') === DiscountAppliesTo::DistinctPhysicalBooks->value),
            Placeholder::make('author_complete_set_notice')
              ->label('Condition collection complète')
              ->content('La remise s\'applique uniquement si le panier contient au moins 1 exemplaire physique de chaque livre publié de l\'auteur sélectionné.')
              ->visible(fn (callable $get): bool => $get('applies_to') === DiscountAppliesTo::AuthorCompleteSet->value),
            Placeholder::make('author_required_books_count')
              ->label('Titres requis')
              ->content(function (callable $get): string {
                $authorId = $get('author_id');

                if (! $authorId) {
                  return 'Sélectionnez un auteur pour voir le nombre de titres requis.';
                }

                $count = app(DiscountService::class)->requiredAuthorBookCount((string) $authorId);

                return $count > 0
                  ? "{$count} livre(s) physique(s) publié(s) de cet auteur."
                  : 'Aucun livre physique publié trouvé pour cet auteur.';
              })
              ->visible(fn (callable $get): bool => $get('applies_to') === DiscountAppliesTo::AuthorCompleteSet->value),
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
              ->helperText('Tous les livres, physiques (quantité ou titres distincts), un livre précis ou la collection complète d\'un auteur.'),
            Select::make('book_id')
              ->label('Livre ciblé')
              ->relationship('book', 'title')
              ->searchable()
              ->preload()
              ->visible(fn (callable $get): bool => $get('applies_to') === DiscountAppliesTo::SpecificBook->value)
              ->helperText('Obligatoire si la remise cible un livre spécifique.'),
            Select::make('author_id')
              ->label('Auteur ciblé')
              ->relationship('author', 'full_name')
              ->searchable()
              ->preload()
              ->required(fn (callable $get): bool => $get('applies_to') === DiscountAppliesTo::AuthorCompleteSet->value)
              ->visible(fn (callable $get): bool => $get('applies_to') === DiscountAppliesTo::AuthorCompleteSet->value)
              ->live()
              ->helperText('La remise exige au moins 1 exemplaire physique de chaque livre publié de cet auteur.'),
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
