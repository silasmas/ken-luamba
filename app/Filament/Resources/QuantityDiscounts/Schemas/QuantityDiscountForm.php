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
          'Choisissez si la remise dépend du nombre d\'exemplaires d\'un même livre ou du nombre de titres différents.',
          [
            TextInput::make('name')
              ->label('Nom de la remise')
              ->required()
              ->maxLength(255)
              ->helperText('Libellé affiché au client (ex. Pack 4 livres -10%).'),
            Select::make('applies_to')
              ->label('Mode de déclenchement')
              ->options(DiscountAppliesTo::adminSelectOptions())
              ->required()
              ->live()
              ->native(false)
              ->helperText('Les deux modes principaux : « livres différents » (pack) ou « même livre » (quantité sur un titre).'),
            Placeholder::make('applies_to_example')
              ->label('Comment ce mode compte le panier')
              ->content(function (callable $get): string {
                $value = $get('applies_to');
                $case = DiscountAppliesTo::tryFrom((string) $value);

                return $case?->adminExample() ?? 'Sélectionnez un mode de déclenchement.';
              })
              ->visible(fn (callable $get): bool => DiscountAppliesTo::tryFrom((string) $get('applies_to')) !== null),
            TextInput::make('min_quantity')
              ->label('Seuil (quantité minimale)')
              ->required(fn (callable $get): bool => $get('applies_to') !== DiscountAppliesTo::AuthorCompleteSet->value)
              ->numeric()
              ->minValue(2)
              ->default(4)
              ->hidden(fn (callable $get): bool => $get('applies_to') === DiscountAppliesTo::AuthorCompleteSet->value)
              ->dehydrated(fn (callable $get): bool => $get('applies_to') !== DiscountAppliesTo::AuthorCompleteSet->value)
              ->helperText(function (callable $get): string {
                return match ($get('applies_to')) {
                  DiscountAppliesTo::DistinctPhysicalBooks->value => 'Nombre de titres physiques différents requis (ex. 4 = pack de 4 livres distincts).',
                  DiscountAppliesTo::SinglePhysicalTitle->value => 'Nombre d\'exemplaires requis sur un seul et même titre (ex. 4 = 4× le même livre).',
                  DiscountAppliesTo::PhysicalOnly->value => 'Somme de tous les exemplaires physiques, titres mélangés possibles.',
                  DiscountAppliesTo::AllBooks->value => 'Somme de tous les articles, y compris ebooks et audio.',
                  DiscountAppliesTo::SpecificBook->value => 'Exemplaires requis du livre ciblé ci-dessous.',
                  default => 'Seuil à atteindre pour déclencher la remise.',
                };
              }),
            Placeholder::make('author_complete_set_notice')
              ->label('Condition collection complète')
              ->content('La remise s\'applique uniquement si le panier contient au moins 1 exemplaire physique de chaque livre publié de l\'auteur sélectionné.')
              ->visible(fn (callable $get): bool => $get('applies_to') === DiscountAppliesTo::AuthorCompleteSet->value),
            Placeholder::make('author_required_books_count')
              ->label('Titres requis')
              ->content(function (callable $get): string {
                $authorId = $get('author_id');

                if (! $authorId) {
                  return 'Sélectionnez un auteur dans la section suivante pour voir le nombre de titres requis.';
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
          'Ciblage & validité',
          'Livre ou auteur concerné, dates et activation.',
          [
            Select::make('book_id')
              ->label('Livre ciblé')
              ->relationship('book', 'title')
              ->searchable()
              ->preload()
              ->visible(fn (callable $get): bool => $get('applies_to') === DiscountAppliesTo::SpecificBook->value)
              ->helperText('Obligatoire : seul ce titre est pris en compte pour le seuil.'),
            Select::make('author_id')
              ->label('Auteur ciblé')
              ->relationship('author', 'full_name')
              ->searchable()
              ->preload()
              ->required(fn (callable $get): bool => $get('applies_to') === DiscountAppliesTo::AuthorCompleteSet->value)
              ->visible(fn (callable $get): bool => $get('applies_to') === DiscountAppliesTo::AuthorCompleteSet->value)
              ->live()
              ->helperText('Collection complète : au moins 1 exemplaire physique de chaque livre publié.'),
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
