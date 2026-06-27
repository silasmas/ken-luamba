<?php

namespace App\Filament\Resources\ShopSettings\Schemas;

use App\Filament\Support\AdminFormLayout;
use App\Filament\Support\ShopCurrencyField;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ShopSettingForm
{
  /**
   * Configure le formulaire des paramètres boutique.
   *
   * @param Schema $schema Schéma Filament à compléter
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    return AdminFormLayout::fullWidth($schema)
      ->components([
        AdminFormLayout::section(
          'Devise',
          'Monnaie unique affichée sur la boutique et utilisée pour les tarifs, commandes et livraisons.',
          [
            ShopCurrencyField::select()
              ->helperText('CDF pour le marché local, USD pour les ventes internationales. Les prix existants dans une autre devise ne seront plus visibles tant qu\'ils ne sont pas recréés dans cette devise.'),
            Placeholder::make('currency_notice')
              ->label('Impact')
              ->content('Après changement, vérifiez les périodes tarifaires et les frais de livraison : ils doivent être saisis dans la devise choisie.'),
          ],
          1,
        ),
        AdminFormLayout::section(
          'Contribution volontaire',
          'Permet aux clients d\'ajouter un montant libre au-delà du total de leur commande au checkout. Entièrement optionnel.',
          [
            Toggle::make('extra_contribution_enabled')
              ->label('Activer la contribution volontaire')
              ->helperText('Affiche un champ optionnel sur la page de paiement.'),
            TextInput::make('extra_contribution_label')
              ->label('Libellé affiché au client')
              ->maxLength(120)
              ->required()
              ->default('Soutien volontaire')
              ->helperText('Titre du bloc sur le checkout (ex. « Soutien volontaire », « Offrande »).'),
            Textarea::make('extra_contribution_help_text')
              ->label('Texte d\'aide')
              ->rows(3)
              ->columnSpanFull()
              ->helperText('Courte explication sous le libellé. Laissez vide pour masquer le texte.'),
          ],
          1,
        ),
      ]);
  }
}
