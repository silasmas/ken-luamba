<?php

namespace App\Filament\Resources\Books\RelationManagers;

use App\Enums\BookFormatType;
use App\Enums\DigitalFileType;
use App\Filament\Support\AdminFormLayout;
use App\Models\BookFormat;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FormatsRelationManager extends RelationManager
{
  protected static string $relationship = 'formats';

  protected static ?string $title = 'Formats';

  /**
   * Configure le formulaire d'un format de livre.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public function form(Schema $schema): Schema
  {
    return AdminFormLayout::fullWidth($schema)
      ->components([
        Section::make('Format & stock')
          ->description('Type de produit. La référence SKU est générée automatiquement à l\'enregistrement.')
          ->schema([
            Grid::make(['default' => 1, 'md' => 2])->schema([
              Select::make('type')
                ->label('Type de format')
                ->options(collect(BookFormatType::cases())->mapWithKeys(
                  fn (BookFormatType $type): array => [$type->value => $type->label()]
                )->all())
                ->required()
                ->native(false)
                ->live()
                ->helperText('Livre relié, broché, ebook ou audio.'),
              Placeholder::make('sku_display')
                ->label('SKU')
                ->content(fn (?BookFormat $record): string => $record?->sku ?? 'Généré automatiquement (ex. KL-EG-HC)'),
              TextInput::make('stock_quantity')
                ->label('Stock')
                ->numeric()
                ->minValue(0)
                ->visible(fn (callable $get): bool => ! in_array($get('type'), [
                  BookFormatType::Ebook->value,
                  BookFormatType::Audiobook->value,
                ], true))
                ->helperText('Quantité physique. Non applicable aux formats numériques.'),
              Toggle::make('is_active')
                ->label('Actif')
                ->default(true)
                ->helperText('Masque ce format du catalogue si désactivé.'),
            ]),
          ])
          ->columnSpanFull(),
        Section::make('Fichier numérique')
          ->description('Ebook ou audio — accès sécurisé après achat. Le partage par lien est optionnel.')
          ->visible(fn (callable $get): bool => in_array($get('type'), [
            BookFormatType::Ebook->value,
            BookFormatType::Audiobook->value,
          ], true))
          ->schema([
            Select::make('digital_file_type')
              ->label('Type de fichier à télécharger')
              ->options(function (callable $get): array {
                $formatType = BookFormatType::tryFrom((string) $get('type'));

                if ($formatType === null) {
                  return [];
                }

                return collect(DigitalFileType::forBookFormat($formatType))
                  ->mapWithKeys(fn (DigitalFileType $type): array => [$type->value => $type->label()])
                  ->all();
              })
              ->required()
              ->native(false)
              ->live()
              ->helperText('Format proposé au client après achat (PDF, EPUB ou MP3).'),
            FileUpload::make('digital_file_path')
              ->label('Fichier numérique')
              ->disk('local')
              ->directory('books/digital')
              ->required()
              ->acceptedFileTypes(function (callable $get): array {
                $fileType = DigitalFileType::tryFrom((string) $get('digital_file_type'));

                return $fileType?->mimeTypes() ?? [];
              })
              ->helperText('Le fichier doit correspondre au type sélectionné. Accès sécurisé après achat.'),
            TextInput::make('digital_max_downloads')
              ->label('Téléchargements max')
              ->numeric()
              ->minValue(1)
              ->maxValue(50)
              ->default(fn (): int => (int) config('digital.max_downloads', 5))
              ->helperText('Nombre de fois que le client peut télécharger le fichier (variable DIGITAL_MAX_DOWNLOADS par défaut).'),
            TextInput::make('digital_stream_expiry_hours')
              ->label('Validité lien lecture (heures)')
              ->numeric()
              ->minValue(1)
              ->maxValue(168)
              ->default(fn (): int => (int) config('digital.stream_expiry_hours', 2))
              ->helperText('Durée du lien signé pour Lire / Écouter en ligne. Après expiration, le client doit rouvrir depuis Ma bibliothèque. N\'affecte pas le fichier déjà téléchargé.'),
            Toggle::make('digital_share_enabled')
              ->label('Autoriser le partage par lien')
              ->default(false)
              ->helperText('Permet au client de créer des liens publics temporaires (EPUB ou audio).'),
            TextInput::make('digital_share_expiry_hours')
              ->label('Durée lien partagé (heures)')
              ->numeric()
              ->minValue(1)
              ->maxValue(168)
              ->default(fn (): int => (int) config('digital.share_expiry_hours', 48))
              ->visible(fn (callable $get): bool => (bool) $get('digital_share_enabled'))
              ->helperText('Temps pendant lequel le destinataire peut ouvrir le lien partagé dans son navigateur.'),
            TextInput::make('digital_share_max_links')
              ->label('Liens de partage max')
              ->numeric()
              ->minValue(1)
              ->maxValue(20)
              ->default(fn (): int => (int) config('digital.share_max_links', 3))
              ->visible(fn (callable $get): bool => (bool) $get('digital_share_enabled'))
              ->helperText('Nombre de liens actifs simultanés que le client peut créer pour ce livre.'),
          ])
          ->columnSpanFull(),
      ]);
  }

  /**
   * Configure le tableau des formats liés au livre.
   *
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('sku')
      ->columns([
        TextColumn::make('type')
          ->label('Format')
          ->formatStateUsing(fn (BookFormatType $state): string => $state->label()),
        TextColumn::make('digital_file_type')
          ->label('Type fichier')
          ->formatStateUsing(fn (?DigitalFileType $state): string => $state?->label() ?? '—')
          ->toggleable(),
        IconColumn::make('digital_share_enabled')
          ->label('Partage')
          ->boolean()
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('digital_max_downloads')
          ->label('Téléch. max')
          ->formatStateUsing(fn (?int $state, BookFormat $record): string => (string) $record->resolvedMaxDownloads())
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('sku')
          ->label('SKU')
          ->searchable(),
        TextColumn::make('stock_quantity')
          ->label('Stock'),
        TextColumn::make('pricing_periods_count')
          ->label('Périodes tarifaires')
          ->counts('pricingPeriods'),
        IconColumn::make('is_active')
          ->label('Actif')
          ->boolean(),
      ])
      ->headerActions([
        CreateAction::make(),
      ])
      ->recordActions([
        EditAction::make(),
        DeleteAction::make(),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
        ]),
      ]);
  }
}
