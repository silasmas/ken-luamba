<?php

namespace App\Filament\Resources\Books\RelationManagers;

use App\Enums\BookFormatType;
use App\Enums\DigitalFileType;
use App\Filament\Support\AdminFormLayout;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
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
          ->description('Type de produit et référence interne.')
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
              TextInput::make('sku')
                ->label('SKU')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->helperText('Référence unique (ex. KL-MPO-HC).'),
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
          ->description('Ebook ou audio — accès sécurisé après achat, non partageable.')
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
