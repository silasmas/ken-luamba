<?php

namespace App\Filament\Resources\ContactSettings\Schemas;

use App\Filament\Support\AdminFormLayout;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ContactSettingForm
{
  /**
   * Configure le formulaire des paramètres de contact.
   *
   * @param Schema $schema Schéma Filament à compléter
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    return AdminFormLayout::fullWidth($schema)
      ->components([
        AdminFormLayout::section(
          'Page contact',
          'Texte affiché sous le titre « Restons en lien. » sur le site public.',
          [
            Textarea::make('intro_description')
              ->label('Description d\'introduction')
              ->required()
              ->rows(4)
              ->maxLength(2000)
              ->helperText('Paragraphe sous le titre principal de la page contact.'),
          ],
          1,
        ),
        AdminFormLayout::section(
          'Coordonnées',
          'Informations affichées dans les cartes de la page contact.',
          [
            TextInput::make('phone_primary')
              ->label('Téléphone principal')
              ->required()
              ->maxLength(60)
              ->helperText('Numéro affiché avec lien d\'appel.'),
            TextInput::make('phone_secondary')
              ->label('Deuxième numéro (WhatsApp)')
              ->required()
              ->maxLength(60)
              ->helperText('Numéro affiché avec lien WhatsApp.'),
            TextInput::make('email')
              ->label('Adresse e-mail')
              ->email()
              ->required()
              ->maxLength(255),
            Textarea::make('physical_address')
              ->label('Adresse physique')
              ->required()
              ->rows(3)
              ->maxLength(1000),
          ],
          2,
        ),
        AdminFormLayout::section(
          'Pied de page',
          'Crédit affiché en bas du site à la place du lien Contact si activé.',
          [
            Toggle::make('show_sdev_credit')
              ->label('Afficher « Designed by SDev »')
              ->default(true)
              ->helperText('Si désactivé, le lien Contact s\'affiche à la place.'),
            TextInput::make('sdev_label')
              ->label('Libellé du crédit')
              ->default('SDev')
              ->maxLength(80)
              ->required()
              ->visible(fn (callable $get): bool => (bool) $get('show_sdev_credit')),
            TextInput::make('sdev_url')
              ->label('URL du crédit')
              ->url()
              ->default('https://silasmas.com')
              ->maxLength(255)
              ->required()
              ->visible(fn (callable $get): bool => (bool) $get('show_sdev_credit')),
          ],
          1,
        ),
      ]);
  }
}
