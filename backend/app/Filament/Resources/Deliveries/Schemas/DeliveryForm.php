<?php

namespace App\Filament\Resources\Deliveries\Schemas;

use App\Enums\DeliveryStatus;
use App\Enums\UserRole;
use App\Filament\Support\AdminFormLayout;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class DeliveryForm
{
  /**
   * Configure le formulaire de suivi d'une livraison.
   *
   * @param Schema $schema Schéma Filament à compléter
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    return AdminFormLayout::fullWidth($schema)
      ->components([
        AdminFormLayout::section(
          'Livraison',
          'Commande associée et livreur assigné.',
          [
            Select::make('order_id')
              ->label('Commande')
              ->relationship('order', 'order_number')
              ->searchable()
              ->required()
              ->disabled()
              ->helperText('Commande physique à livrer ou à retirer.'),
            Select::make('courier_id')
              ->label('Livreur')
              ->relationship(
                'courier',
                'full_name',
                fn ($query) => $query->whereIn('role', [UserRole::Courier->value, UserRole::Admin->value]),
              )
              ->searchable()
              ->preload()
              ->live()
              ->helperText('Choisissez un livreur puis cliquez sur « Maintenant » pour assigner.'),
            Select::make('status')
              ->label('Statut livraison')
              ->options(collect(DeliveryStatus::cases())->mapWithKeys(
                fn (DeliveryStatus $status) => [$status->value => $status->label()]
              )->all())
              ->default(DeliveryStatus::Pending->value)
              ->required()
              ->native(false)
              ->helperText('Passe à « Assignée » quand vous cliquez sur Maintenant.'),
          ],
        ),
        AdminFormLayout::section(
          'Suivi & notes',
          'Date d\'assignation admin ; date de livraison renseignée par le livreur.',
          [
            DateTimePicker::make('assigned_at')
              ->label('Assignée le')
              ->native(false)
              ->seconds(false)
              ->nullable()
              ->displayFormat('d/m/Y H:i')
              ->helperText('Cliquez sur « Maintenant » pour enregistrer l\'assignation à l\'heure actuelle.')
              ->suffixAction(
                Action::make('setAssignedNow')
                  ->label('Maintenant')
                  ->icon(Heroicon::OutlinedClock)
                  ->color('success')
                  ->action(function ($set): void {
                    $set('assigned_at', now());
                    $set('status', DeliveryStatus::Assigned->value);
                  }),
              ),
            DateTimePicker::make('delivered_at')
              ->label('Livrée / retirée le')
              ->native(false)
              ->seconds(false)
              ->disabled()
              ->dehydrated(false)
              ->displayFormat('d/m/Y H:i')
              ->placeholder('—')
              ->helperText('Renseignée automatiquement par le livreur lors du scan QR et de la photo preuve.'),
            Placeholder::make('delivery_info')
              ->label('Rappel')
              ->content('La date de livraison ne se modifie pas ici : le livreur la valide depuis son espace (/livreur) après scan du QR client.')
              ->columnSpanFull(),
            Textarea::make('notes')
              ->label('Notes')
              ->rows(3)
              ->columnSpanFull()
              ->helperText('Remarques internes ou motif de litige.'),
          ],
          1,
        ),
      ]);
  }
}
