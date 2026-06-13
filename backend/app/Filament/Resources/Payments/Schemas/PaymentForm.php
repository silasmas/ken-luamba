<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Filament\Support\AdminFormLayout;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PaymentForm
{
  /**
   * Configure le formulaire de consultation d'un paiement.
   *
   * @param Schema $schema Schéma Filament à compléter
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    return AdminFormLayout::fullWidth($schema)
      ->components([
        AdminFormLayout::section(
          'Transaction',
          'Lien commande et référence FlexPay.',
          [
            Select::make('order_id')
              ->label('Commande')
              ->relationship('order', 'order_number')
              ->searchable()
              ->disabled()
              ->helperText('Commande associée à ce paiement.'),
            TextInput::make('provider_reference')
              ->label('Réf. FlexPay')
              ->maxLength(255)
              ->helperText('Numéro orderNumber retourné par FlexPay.'),
            Select::make('status')
              ->label('Statut')
              ->options(collect(PaymentStatus::cases())->mapWithKeys(
                fn (PaymentStatus $status) => [$status->value => $status->label()]
              )->all())
              ->required()
              ->native(false)
              ->helperText('En attente, en cours, payé, échoué ou annulé.'),
            Select::make('channel')
              ->label('Canal')
              ->options(collect(PaymentChannel::cases())->mapWithKeys(
                fn (PaymentChannel $channel) => [$channel->value => $channel->label()]
              )->all())
              ->native(false)
              ->helperText('Mobile Money ou carte bancaire.'),
          ],
        ),
        AdminFormLayout::section(
          'Montant & contact',
          'Détails financiers et téléphone Mobile Money.',
          [
            TextInput::make('amount')
              ->label('Montant')
              ->numeric()
              ->disabled()
              ->helperText('Montant débité pour cette transaction.'),
            TextInput::make('currency')
              ->label('Devise')
              ->disabled()
              ->helperText('Devise du paiement.'),
            TextInput::make('phone')
              ->label('Téléphone Mobile Money')
              ->tel()
              ->helperText('Numéro utilisé pour le paiement mobile (243…).'),
            DateTimePicker::make('paid_at')
              ->label('Payé le')
              ->helperText('Horodatage de confirmation FlexPay.'),
          ],
        ),
        AdminFormLayout::section(
          'Technique',
          'Données brutes renvoyées par la passerelle.',
          [
            KeyValue::make('metadata')
              ->label('Métadonnées FlexPay')
              ->columnSpanFull()
              ->helperText('Réponse JSON FlexPay pour le support technique.'),
          ],
          1,
        ),
      ]);
  }
}
