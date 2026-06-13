<?php

namespace App\Filament\Resources\Books\Schemas;

use App\Filament\Support\AdminFormLayout;
use App\Models\Author;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BookForm
{
  /**
   * Configure le formulaire de gestion d'un livre.
   *
   * @param Schema $schema Schéma Filament à compléter
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    return AdminFormLayout::fullWidth($schema)
      ->components([
        AdminFormLayout::section(
          'Informations générales',
          'Titre, auteur et identifiant URL du livre.',
          [
            Select::make('author_id')
              ->label('Auteur')
              ->relationship('author', 'full_name')
              ->searchable()
              ->preload()
              ->required()
              ->default(fn (): ?string => Author::query()->where('is_primary', true)->value('id'))
              ->helperText('Auteur associé à cet ouvrage.'),
            TextInput::make('title')
              ->label('Titre')
              ->required()
              ->maxLength(255)
              ->live(onBlur: true)
              ->afterStateUpdated(function (?string $state, callable $set): void {
                $set('slug', Str::slug($state ?? ''));
              })
              ->helperText('Titre affiché dans le catalogue.'),
            TextInput::make('slug')
              ->label('Slug URL')
              ->required()
              ->unique(ignoreRecord: true)
              ->maxLength(255)
              ->helperText('Adresse /livres/{slug} — généré depuis le titre.'),
          ],
        ),
        AdminFormLayout::section(
          'Contenu',
          'Descriptions et visuels du livre.',
          [
            Textarea::make('description')
              ->label('Description')
              ->rows(5)
              ->columnSpanFull()
              ->helperText('Présentation commerciale visible sur la fiche livre.'),
            Textarea::make('author_note')
              ->label('Mot de l\'auteur')
              ->rows(3)
              ->columnSpanFull()
              ->helperText('Message personnel du pasteur pour ce livre.'),
            FileUpload::make('cover_image')
              ->label('Couverture')
              ->image()
              ->disk('public')
              ->visibility('public')
              ->directory('books/covers')
              ->columnSpanFull()
              ->helperText('Image de couverture (ratio portrait recommandé).'),
          ],
          1,
        ),
        AdminFormLayout::section(
          'Publication & mise en avant',
          'Contrôle de la visibilité dans la boutique.',
          [
            Toggle::make('is_published')
              ->label('Publié')
              ->helperText('Visible dans le catalogue en ligne.'),
            Toggle::make('is_featured')
              ->label('Mis en avant')
              ->helperText('Affiché sur la page d\'accueil.'),
            TextInput::make('sort_order')
              ->label('Ordre d\'affichage')
              ->numeric()
              ->default(0)
              ->helperText('Plus petit = affiché en premier.'),
            DateTimePicker::make('published_at')
              ->label('Date de publication')
              ->helperText('Date officielle de mise en vente.'),
          ],
        ),
      ]);
  }
}
