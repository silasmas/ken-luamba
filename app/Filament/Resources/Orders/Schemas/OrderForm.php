<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Filament\Support\AdminFormLayout;
use App\Models\Order;
use App\Support\OrderAdminFormatter;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
{
  /**
   * Configure le formulaire de consultation/édition d'une commande.
   *
   * @param Schema $schema Schéma Filament à compléter
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    return AdminFormLayout::fullWidth($schema)
      ->components([
        AdminFormLayout::section(
          'Commande',
          'Référence et statut du cycle de vie.',
          [
            TextInput::make('order_number')
              ->label('N° commande')
              ->disabled()
              ->helperText('Identifiant unique généré automatiquement.'),
            Select::make('user_id')
              ->label('Client')
              ->relationship('user', 'full_name')
              ->searchable()
              ->disabled()
              ->helperText('Compte client ayant passé la commande.'),
            Select::make('status')
              ->label('Statut')
              ->options(collect(OrderStatus::cases())->mapWithKeys(
                fn (OrderStatus $status) => [$status->value => $status->label()]
              )->all())
              ->required()
              ->native(false)
              ->helperText('Étape actuelle : paiement, préparation, livraison…'),
          ],
        ),
        AdminFormLayout::section(
          'Livraison & retrait',
          'Mode de réception choisi par le client.',
          [
            Select::make('fulfillment_type')
              ->label('Mode de réception')
              ->options(collect(FulfillmentType::cases())->mapWithKeys(
                fn (FulfillmentType $type) => [$type->value => $type->label()]
              )->all())
              ->native(false)
              ->helperText('Livraison à domicile ou retrait sur place.'),
            Select::make('pickup_point_id')
              ->label('Point de retrait')
              ->relationship('pickupPoint', 'name')
              ->searchable()
              ->helperText('Renseigné si le client a choisi le retrait.'),
            KeyValue::make('shipping_address')
              ->label('Adresse de livraison')
              ->columnSpanFull()
              ->helperText('Rue, ville et téléphone pour la livraison.'),
            Textarea::make('notes')
              ->label('Notes client')
              ->rows(3)
              ->columnSpanFull()
              ->helperText('Instructions ou remarques laissées au checkout.'),
          ],
        ),
        AdminFormLayout::section(
          'Montants',
          'Totaux financiers de la commande.',
          [
            TextInput::make('subtotal')
              ->label('Sous-total')
              ->numeric()
              ->disabled()
              ->helperText('Somme des lignes avant remise.'),
            TextInput::make('discount_amount')
              ->label('Remise')
              ->numeric()
              ->disabled()
              ->helperText('Montant déduit (pack quantité, promo).'),
            TextInput::make('shipping_amount')
              ->label('Frais de livraison')
              ->numeric()
              ->disabled()
              ->helperText('Calculé automatiquement selon les paramètres de livraison.'),
            TextInput::make('extra_contribution_amount')
              ->label('Contribution volontaire')
              ->numeric()
              ->disabled()
              ->helperText('Montant libre ajouté par le client au-delà du total articles + livraison.'),
            Placeholder::make('books_received_status')
              ->label('Livre reçu')
              ->content(fn (?Order $record): string => $record
                ? OrderAdminFormatter::booksReceivedLabel($record)
                : '—')
              ->helperText(fn (?Order $record): ?string => $record
                ? OrderAdminFormatter::booksPendingSummary($record)
                : null)
              ->visible(fn (?Order $record): bool => $record !== null && $record->hasPhysicalItems()),
            Placeholder::make('payment_mode_status')
              ->label('Mode d\'achat')
              ->content(fn (?Order $record): string => $record
                ? OrderAdminFormatter::paymentModeLabel($record)
                : '—')
              ->helperText(fn (?Order $record): ?string => $record
                ? OrderAdminFormatter::paymentModeDescription($record)
                : null),
            TextInput::make('total')
              ->label('Total')
              ->numeric()
              ->disabled()
              ->helperText('Montant final payé par le client.'),
            TextInput::make('currency')
              ->label('Devise')
              ->disabled()
              ->helperText('Devise de la transaction (CDF, USD).'),
          ],
        ),
        AdminFormLayout::section(
          'Dates clés',
          'Horodatage des étapes importantes.',
          [
            DateTimePicker::make('paid_at')
              ->label('Payée le')
              ->helperText('Date de confirmation du paiement FlexPay.'),
            DateTimePicker::make('completed_at')
              ->label('Terminée le')
              ->helperText('Date de clôture après validation client.'),
          ],
        ),
      ]);
  }
}
