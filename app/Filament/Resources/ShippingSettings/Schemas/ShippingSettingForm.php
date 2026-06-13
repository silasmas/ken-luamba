<?php

namespace App\Filament\Resources\ShippingSettings\Schemas;

use App\Enums\InternationalShippingPolicy;
use App\Enums\ShippingPricingMode;
use App\Filament\Support\AdminFormLayout;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ShippingSettingForm
{
  /**
   * Configure le formulaire des paramètres de livraison.
   *
   * @param Schema $schema Schéma Filament à compléter
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    return AdminFormLayout::fullWidth($schema)
      ->components([
        AdminFormLayout::section(
          'Mode national',
          'Définissez d\'abord les villes couvertes (menu Villes de livraison), puis les zones et communes par ville.',
          [
            Toggle::make('is_active')
              ->label('Livraison active')
              ->helperText('Désactive toutes les livraisons à domicile si inactif.'),
            Select::make('pricing_mode')
              ->label('Mode de tarification nationale')
              ->options(collect(ShippingPricingMode::cases())->mapWithKeys(
                fn (ShippingPricingMode $mode): array => [$mode->value => $mode->label()]
              )->all())
              ->required()
              ->native(false)
              ->live()
              ->helperText('Prix fixe pour tout le pays, ou montants différents par zone.'),
            TextInput::make('fixed_amount')
              ->label('Montant fixe national')
              ->numeric()
              ->minValue(0)
              ->visible(fn (callable $get): bool => $get('pricing_mode') === ShippingPricingMode::Fixed->value)
              ->helperText('Appliqué à toutes les livraisons dans le pays.'),
            TextInput::make('currency')
              ->label('Devise')
              ->maxLength(3)
              ->default('CDF')
              ->helperText('Devise des frais de livraison (CDF, USD…).'),
            TextInput::make('domestic_country_code')
              ->label('Code pays national (ISO)')
              ->maxLength(2)
              ->default('CD')
              ->helperText('Ex. CD pour la RDC. Les autres pays déclenchent la politique internationale.'),
            TextInput::make('domestic_country_name')
              ->label('Nom du pays national')
              ->maxLength(120)
              ->helperText('Libellé affiché au client sur le checkout.'),
          ],
        ),
        AdminFormLayout::section(
          'Politique internationale',
          'Règles appliquées quand le pays de livraison est différent du pays national.',
          [
            Select::make('international_policy')
              ->label('Politique hors pays')
              ->options(collect(InternationalShippingPolicy::cases())->mapWithKeys(
                fn (InternationalShippingPolicy $policy): array => [$policy->value => $policy->label()]
              )->all())
              ->required()
              ->native(false)
              ->live()
              ->helperText('Montant fixe, sur devis ou livraison bloquée.'),
            TextInput::make('international_amount')
              ->label('Montant fixe international')
              ->numeric()
              ->minValue(0)
              ->visible(fn (callable $get): bool => $get('international_policy') === InternationalShippingPolicy::Fixed->value)
              ->helperText('Frais appliqués automatiquement hors du pays national.'),
            Textarea::make('international_message')
              ->label('Message international')
              ->rows(3)
              ->columnSpanFull()
              ->helperText('Affiché au client (devis ou indisponibilité internationale).'),
          ],
          1,
        ),
      ]);
  }
}
