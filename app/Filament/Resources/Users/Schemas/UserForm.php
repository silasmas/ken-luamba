<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Filament\Support\AdminFormLayout;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
  /**
   * Configure le formulaire de gestion d'un utilisateur.
   *
   * @param Schema $schema Schéma Filament à compléter
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    return AdminFormLayout::fullWidth($schema)
      ->components([
        AdminFormLayout::section(
          'Profil',
          'Identité et coordonnées de l\'utilisateur.',
          [
            TextInput::make('full_name')
              ->label('Nom complet')
              ->required()
              ->maxLength(255)
              ->helperText('Nom affiché dans l\'espace membre et les commandes.'),
            TextInput::make('name')
              ->label('Identifiant court')
              ->required()
              ->maxLength(255)
              ->helperText('Nom technique (connexion admin, logs).'),
            TextInput::make('email')
              ->label('Email')
              ->email()
              ->required()
              ->unique(ignoreRecord: true)
              ->helperText('Utilisé pour OTP, notifications et connexion admin.'),
            TextInput::make('phone')
              ->label('Téléphone principal')
              ->tel()
              ->maxLength(20)
              ->helperText('Format 243XXXXXXXXX — renseigné à l\'inscription.'),
            TextInput::make('secondary_phone')
              ->label('Téléphone Mobile Money')
              ->tel()
              ->maxLength(20)
              ->helperText('Second numéro confirmé après paiement Mobile Money.'),
          ],
        ),
        AdminFormLayout::section(
          'Rôles & accès',
          'Rôle métier API et permissions Filament Shield.',
          [
            Select::make('role')
              ->label('Rôle métier')
              ->options(collect(UserRole::cases())->mapWithKeys(
                fn (UserRole $role) => [$role->value => $role->label()]
              )->all())
              ->required()
              ->native(false)
              ->default(UserRole::Client->value)
              ->helperText('Client, livreur, éditeur ou admin — utilisé par l\'API.'),
            Select::make('roles')
              ->label('Rôles Shield (permissions)')
              ->relationship('roles', 'name')
              ->multiple()
              ->preload()
              ->searchable()
              ->helperText('Contrôle l\'accès aux menus et actions du back-office.'),
            Toggle::make('is_active')
              ->label('Compte actif')
              ->default(true)
              ->helperText('Désactivez pour bloquer connexion et API.'),
            DateTimePicker::make('email_verified_at')
              ->label('Email vérifié le')
              ->helperText('Renseigné après validation OTP.'),
          ],
        ),
        AdminFormLayout::section(
          'Sécurité',
          'Mot de passe pour la connexion admin Filament.',
          [
            TextInput::make('password')
              ->label('Mot de passe')
              ->password()
              ->revealable()
              ->required(fn ($livewire): bool => $livewire instanceof CreateRecord)
              ->dehydrated(fn (?string $state): bool => filled($state))
              ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
              ->helperText('Obligatoire à la création. Laissez vide pour ne pas modifier.'),
          ],
          1,
        ),
      ]);
  }
}
