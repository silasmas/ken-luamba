<?php

namespace App\Filament\Resources\PricingPeriods\Schemas;

use App\Enums\PricingPeriodType;
use App\Filament\Support\AdminFormLayout;
use App\Models\ShopSetting;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PricingPeriodForm
{
  /**
   * Configure le formulaire d'une période tarifaire.
   *
   * @param Schema $schema Schéma Filament à compléter
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    return AdminFormLayout::fullWidth($schema)
      ->components([
        AdminFormLayout::section(
          'Cible & libellé',
          'Format concerné et nom de la période.',
          [
            Select::make('book_format_id')
              ->label('Format de livre')
              ->relationship('bookFormat', 'sku')
              ->getOptionLabelFromRecordUsing(
                fn ($record): string => $record->book?->title.' — '.$record->type->label().' ('.$record->sku.')'
              )
              ->searchable()
              ->preload()
              ->required()
              ->helperText('Format auquel ce tarif s\'applique.'),
            TextInput::make('label')
              ->label('Libellé')
              ->required()
              ->maxLength(255)
              ->helperText('Ex. Pré-commande lancement, Prix standard.'),
            Select::make('type')
              ->label('Type de période')
              ->options(collect(PricingPeriodType::cases())->mapWithKeys(
                fn (PricingPeriodType $type): array => [$type->value => $type->label()]
              )->all())
              ->required()
              ->native(false)
              ->helperText('Pré-commande, vente régulière ou promotion.'),
          ],
        ),
        AdminFormLayout::section(
          'Tarif & calendrier',
          'Prix et fenêtre de validité.',
          [
            Hidden::make('currency')
              ->default(fn (): string => ShopSetting::currencyCode())
              ->dehydrated(),
            TextInput::make('price')
              ->label('Prix')
              ->required()
              ->numeric()
              ->prefix(fn (): string => ShopSetting::currencyCode())
              ->helperText(fn (): string => ShopSetting::currencyCode() === 'USD'
                ? 'Montant en dollars américains (devise boutique).'
                : 'Montant en francs congolais (devise boutique).'),
            DateTimePicker::make('start_at')
              ->label('Début')
              ->required()
              ->helperText('Date/heure d\'activation du tarif.'),
            DateTimePicker::make('end_at')
              ->label('Fin')
              ->required()
              ->helperText('Date/heure de fin de cette période.'),
            Toggle::make('is_active')
              ->label('Active')
              ->default(true)
              ->helperText('Désactivez pour suspendre sans supprimer.'),
          ],
        ),
      ]);
  }
}
