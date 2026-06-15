<?php

namespace App\Filament\Resources\Books\Schemas;

use App\Filament\Support\AdminFormLayout;
use App\Filament\Support\BookReleasePlaceholderHelper;
use App\Models\Author;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
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
            TextInput::make('subtitle')
              ->label('Sous-titre')
              ->maxLength(255)
              ->helperText('Accroche affichée sous le titre sur la fiche livre.'),
            TextInput::make('tagline')
              ->label('Tagline')
              ->maxLength(255)
              ->helperText('Phrase courte pour les cartes et suggestions.'),
            TextInput::make('category')
              ->label('Catégorie')
              ->maxLength(255)
              ->helperText('Ex. Théologie pratique · Ecclésiologie'),
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
            Textarea::make('about_paragraphs')
              ->label('À propos (paragraphes)')
              ->rows(8)
              ->columnSpanFull()
              ->formatStateUsing(fn ($state) => is_array($state) ? implode("\n\n", $state) : '')
              ->dehydrateStateUsing(
                fn ($state) => array_values(array_filter(preg_split("/\R\R+/", (string) $state) ?: [])),
              )
              ->helperText('Un paragraphe par bloc, séparés par une ligne vide.'),
            Textarea::make('themes')
              ->label('Thèmes / tags')
              ->rows(3)
              ->formatStateUsing(fn ($state) => is_array($state) ? implode("\n", $state) : '')
              ->dehydrateStateUsing(
                fn ($state) => array_values(array_filter(array_map('trim', preg_split("/\R/", (string) $state) ?: []))),
              )
              ->helperText('Un thème par ligne.'),
            Textarea::make('excerpt')
              ->label('Extrait (JSON)')
              ->rows(10)
              ->columnSpanFull()
              ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : $state)
              ->dehydrateStateUsing(function ($state) {
                if (is_array($state)) {
                  return $state;
                }

                $decoded = json_decode((string) $state, true);

                return is_array($decoded) ? $decoded : [];
              })
              ->helperText('Pages de l\'aperçu feuilletable au format JSON.'),
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
          'Fiche éditoriale',
          'Métadonnées affichées sur la page détail.',
          [
            TextInput::make('page_count')
              ->label('Nombre de pages')
              ->numeric()
              ->minValue(1),
            TextInput::make('reading_time_minutes')
              ->label('Durée de lecture (minutes)')
              ->numeric()
              ->minValue(1)
              ->helperText('Ex. 360 pour 6 h de lecture.'),
            TextInput::make('language')
              ->label('Langue')
              ->default('Français')
              ->maxLength(80),
            DatePicker::make('release_date')
              ->label('Date de sortie officielle')
              ->helperText('Utilisée pour le compte à rebours de précommande.'),
            TextInput::make('accent_color')
              ->label('Couleur d\'accent')
              ->default('#1b1f2a')
              ->maxLength(7),
            TextInput::make('preorder_campaign_goal')
              ->label('Objectif précommandes')
              ->numeric()
              ->minValue(0),
            TextInput::make('preorder_reserved_count')
              ->label('Précommandes enregistrées')
              ->numeric()
              ->default(0)
              ->minValue(0),
            Textarea::make('preorder_campaign_bonuses')
              ->label('Avantages campagne')
              ->rows(4)
              ->formatStateUsing(fn ($state) => is_array($state) ? implode("\n", $state) : '')
              ->dehydrateStateUsing(
                fn ($state) => array_values(array_filter(array_map('trim', preg_split("/\R/", (string) $state) ?: []))),
              )
              ->helperText('Un avantage par ligne (ex. livraison prioritaire).'),
          ],
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
        AdminFormLayout::section(
          'Alertes sortie',
          'Modèles d\'e-mail pour prévenir les inscrits et envoi automatique programmé.',
          [
            Placeholder::make('release_variables')
              ->label('Variables disponibles')
              ->content(fn () => BookReleasePlaceholderHelper::toHtml())
              ->columnSpanFull(),
            Repeater::make('release_notification_messages')
              ->label('Modèles d\'e-mail')
              ->schema([
                Hidden::make('id')
                  ->default(fn (): string => (string) Str::uuid()),
                TextInput::make('label')
                  ->label('Nom du modèle')
                  ->required()
                  ->maxLength(120),
                TextInput::make('email_subject')
                  ->label('Objet e-mail')
                  ->required()
                  ->maxLength(255),
                Textarea::make('body')
                  ->label('Contenu du message')
                  ->required()
                  ->rows(8)
                  ->columnSpanFull(),
              ])
              ->columns(2)
              ->collapsible()
              ->itemLabel(fn (array $state): string => $state['label'] ?? 'Modèle')
              ->addActionLabel('Ajouter un modèle')
              ->columnSpanFull(),
            Toggle::make('release_auto_notify_enabled')
              ->label('Envoi automatique activé')
              ->helperText('Envoie automatiquement l\'e-mail aux inscrits non notifiés.'),
            DateTimePicker::make('release_auto_notify_at')
              ->label('Date et heure d\'envoi automatique')
              ->seconds(false)
              ->helperText('L\'envoi démarre dès que cette date est atteinte (tâche planifiée).'),
          ],
        ),
      ]);
  }
}
