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
            Textarea::make('roles')
              ->label('Titres / fonctions')
              ->rows(4)
              ->columnSpanFull()
              ->formatStateUsing(fn ($state) => is_array($state) ? implode("\n", $state) : '')
              ->dehydrateStateUsing(
                fn ($state) => array_values(array_filter(array_map('trim', preg_split("/\R/", (string) $state) ?: []))),
              )
              ->helperText('Un titre ou une fonction par ligne — affichés sur l\'accueil et la page auteur.'),
            TextInput::make('title')
              ->label('Titre / fonction (résumé court)')
              ->placeholder('Pasteur · Auteur · Conférencier')
              ->maxLength(255)
              ->helperText('Résumé court sous le nom (secours si la liste ci-dessus est vide).'),
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
              ->label('Photo de profil (générique)')
              ->image()
              ->disk('public')
              ->visibility('public')
              ->directory('authors/profiles')
              ->helperText('Portrait utilisé en secours si les emplacements ci-dessous sont vides.'),
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
              ->helperText('Clés conseillées : facebook, instagram, x, youtube. Valeur = URL complète.'),
          ],
        ),
        AdminFormLayout::section(
          'Photos accueil — Hero',
          'Deux images affichées en haut de la page d\'accueil (grande + petite en surimpression).',
          [
            FileUpload::make('home_hero_primary_image')
              ->label('Photo principale (hero)')
              ->image()
              ->disk('public')
              ->visibility('public')
              ->directory('authors/home-hero')
              ->helperText('Portrait vertical, format JPG/PNG.'),
            FileUpload::make('home_hero_overlay_image')
              ->label('Photo secondaire (hero)')
              ->image()
              ->disk('public')
              ->visibility('public')
              ->directory('authors/home-hero')
              ->helperText('Petite photo en surimpression sur le hero.'),
          ],
        ),
        AdminFormLayout::section(
          'Photos accueil — Rubrique auteur',
          'Deux images pour la section « L\'auteur » en bas de la page d\'accueil.',
          [
            FileUpload::make('home_section_primary_image')
              ->label('Photo principale (rubrique auteur)')
              ->image()
              ->disk('public')
              ->visibility('public')
              ->directory('authors/home-section')
              ->helperText('Grande photo de la rubrique auteur sur l\'accueil.'),
            FileUpload::make('home_section_overlay_image')
              ->label('Photo secondaire (rubrique auteur)')
              ->image()
              ->disk('public')
              ->visibility('public')
              ->directory('authors/home-section')
              ->helperText('Petite photo en surimpression.'),
          ],
        ),
        AdminFormLayout::section(
          'Photos page auteur',
          'Deux images affichées sur la page /auteur.',
          [
            FileUpload::make('page_primary_image')
              ->label('Photo principale (page auteur)')
              ->image()
              ->disk('public')
              ->visibility('public')
              ->directory('authors/page')
              ->helperText('Grande photo en tête de la page auteur.'),
            FileUpload::make('page_overlay_image')
              ->label('Photo secondaire (page auteur)')
              ->image()
              ->disk('public')
              ->visibility('public')
              ->directory('authors/page')
              ->helperText('Petite photo en surimpression sur la page auteur.'),
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
