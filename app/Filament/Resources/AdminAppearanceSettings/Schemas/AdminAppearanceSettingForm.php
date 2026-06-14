<?php

namespace App\Filament\Resources\AdminAppearanceSettings\Schemas;

use App\Filament\Support\AdminFormLayout;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AdminAppearanceSettingForm
{
  /**
   * Configure le formulaire des paramètres d'apparence admin.
   *
   * @param Schema $schema Schéma Filament à compléter
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    return AdminFormLayout::fullWidth($schema)
      ->components([
        AdminFormLayout::section(
          'Identité',
          'Titre et logos affichés dans l\'interface d\'administration.',
          [
            TextInput::make('site_title')
              ->label('Titre du site')
              ->required()
              ->maxLength(120)
              ->helperText('Nom affiché dans la barre latérale et sur la page de connexion.'),
            FileUpload::make('logo_path')
              ->label('Logo')
              ->image()
              ->disk('public')
              ->visibility('public')
              ->directory('admin/branding')
              ->helperText('Laissez vide pour conserver le logo par défaut.'),
            FileUpload::make('favicon_path')
              ->label('Favicon')
              ->image()
              ->disk('public')
              ->visibility('public')
              ->directory('admin/branding')
              ->helperText('Icône de l\'onglet navigateur (optionnel).'),
          ],
        ),
        AdminFormLayout::section(
          'Couleurs',
          'Personnalisez les couleurs des textes, boutons, menu actif et champs au focus.',
          [
            ColorPicker::make('color_primary')
              ->label('Couleur primaire')
              ->required()
              ->hex()
              ->default('#2563eb'),
            ColorPicker::make('color_button_text')
              ->label('Texte des boutons primaires')
              ->required()
              ->hex()
              ->default('#ffffff'),
            ColorPicker::make('color_body_text')
              ->label('Texte général')
              ->required()
              ->hex()
              ->default('#0f172a'),
            ColorPicker::make('color_menu_active')
              ->label('Fond du menu actif')
              ->required()
              ->hex()
              ->default('#2563eb'),
            ColorPicker::make('color_menu_active_text')
              ->label('Texte du menu actif')
              ->required()
              ->hex()
              ->default('#ffffff'),
            ColorPicker::make('color_input_focus')
              ->label('Contour des champs actifs')
              ->required()
              ->hex()
              ->default('#2563eb'),
          ],
          3,
        ),
        AdminFormLayout::section(
          'Navigation',
          'Comportement du menu latéral.',
          [
            Toggle::make('sidebar_collapsible')
              ->label('Menu latéral pliable')
              ->default(true)
              ->helperText('Permet de réduire le menu sur ordinateur pour gagner de l\'espace.'),
          ],
          1,
        ),
        AdminFormLayout::section(
          'SMS Kecel',
          'L\'API solde Kecel peut être indisponible : saisissez un solde manuel en secours.',
          [
            TextInput::make('sms_manual_balance')
              ->label('Solde SMS manuel')
              ->numeric()
              ->minValue(0)
              ->nullable()
              ->helperText('Utilisé si l\'API Kecel ne renvoie pas le solde. Laissez vide pour afficher uniquement l\'API.'),
          ],
          1,
        ),
      ]);
  }
}
