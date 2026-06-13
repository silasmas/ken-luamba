<?php

namespace App\Filament\Resources\Authors\Schemas;

use App\Filament\Support\AdminFormLayout;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AuthorForm
{
  /**
   * Configure le formulaire de gestion d'un auteur.
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
          'Informations principales affichées sur la page auteur.',
          [
            TextInput::make('full_name')
              ->label('Nom complet')
              ->required()
              ->maxLength(255)
              ->live(onBlur: true)
              ->afterStateUpdated(function (?string $state, callable $set): void {
                $set('slug', Str::slug($state ?? ''));
              })
              ->helperText('Nom public du pasteur ou de l\'auteur.'),
            TextInput::make('slug')
              ->label('Slug URL')
              ->required()
              ->unique(ignoreRecord: true)
              ->maxLength(255)
              ->helperText('Identifiant utilisé dans l\'adresse /auteur/{slug}.'),
            TextInput::make('title')
              ->label('Titre / fonction')
              ->placeholder('Pasteur, auteur, conférencier')
              ->maxLength(255)
              ->helperText('Courte mention sous le nom (ex. Pasteur, auteur).'),
          ],
        ),
        AdminFormLayout::section(
          'Biographie',
          'Textes longs pour la page profil.',
          [
            Textarea::make('short_bio')
              ->label('Biographie courte')
              ->rows(3)
              ->columnSpanFull()
              ->helperText('Résumé affiché dans les listes et encarts.'),
            Textarea::make('full_bio')
              ->label('Biographie complète')
              ->rows(8)
              ->columnSpanFull()
              ->helperText('Texte détaillé de la page auteur.'),
            Textarea::make('featured_quote')
              ->label('Citation mise en avant')
              ->rows(2)
              ->columnSpanFull()
              ->helperText('Citation phare mise en valeur sur le site.'),
          ],
          1,
        ),
        AdminFormLayout::section(
          'Médias & réseaux',
          'Images et liens externes.',
          [
            FileUpload::make('profile_image')
              ->label('Photo de profil')
              ->image()
              ->disk('public')
              ->visibility('public')
              ->directory('authors/profiles')
              ->helperText('Portrait carré ou portrait, format JPG/PNG.'),
            FileUpload::make('cover_image')
              ->label('Image de couverture')
              ->image()
              ->disk('public')
              ->visibility('public')
              ->directory('authors/covers')
              ->helperText('Bannière large pour la page auteur.'),
            KeyValue::make('social_links')
              ->label('Réseaux sociaux')
              ->keyLabel('Plateforme')
              ->valueLabel('URL')
              ->columnSpanFull()
              ->helperText('Liens Facebook, YouTube, Instagram, etc.'),
          ],
        ),
        AdminFormLayout::section(
          'Publication & SEO',
          'Visibilité sur le site et référencement.',
          [
            Toggle::make('is_primary')
              ->label('Auteur principal du site')
              ->helperText('Un seul auteur principal (Ken Luamba par défaut).'),
            Toggle::make('is_published')
              ->label('Publié')
              ->default(true)
              ->helperText('Décochez pour masquer la page auteur.'),
            TextInput::make('meta_title')
              ->label('Titre SEO')
              ->maxLength(255)
              ->helperText('Titre pour Google (60 caractères max conseillé).'),
            Textarea::make('meta_description')
              ->label('Description SEO')
              ->rows(2)
              ->columnSpanFull()
              ->helperText('Résumé pour les moteurs de recherche.'),
          ],
        ),
      ]);
  }
}
