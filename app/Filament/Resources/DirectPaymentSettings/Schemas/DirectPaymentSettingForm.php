<?php

namespace App\Filament\Resources\DirectPaymentSettings\Schemas;

use App\Enums\BookFormatType;
use App\Filament\Support\AdminFormLayout;
use App\Models\BookFormat;
use App\Models\DirectPaymentSetting;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

/**
 * Formulaire Filament des paramètres paiement direct.
 */
class DirectPaymentSettingForm
{
  /**
   * Configure le formulaire des paramètres paiement direct.
   *
   * @param Schema $schema Schéma Filament à compléter
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    $formatOptions = BookFormat::query()
      ->active()
      ->whereIn('type', [
        BookFormatType::Hardcover->value,
        BookFormatType::Paperback->value,
      ])
      ->whereHas('book', fn ($query) => $query->published())
      ->with('book')
      ->get()
      ->mapWithKeys(fn (BookFormat $format): array => [
        $format->id => ($format->book?->title ?? 'Livre').' — '.$format->type->label(),
      ])
      ->all();

    return AdminFormLayout::fullWidth($schema)
      ->components([
        AdminFormLayout::section(
          'Page publique',
          'Lien et textes affichés sur la page paiement direct (frontend).',
          [
            Toggle::make('is_enabled')
              ->label('Activer le paiement direct')
              ->helperText('Désactive l\'API catalogue/checkout si off.'),
            TextInput::make('title')
              ->label('Titre')
              ->required()
              ->maxLength(120),
            Textarea::make('message')
              ->label('Message d\'introduction')
              ->rows(3)
              ->columnSpanFull(),
            Placeholder::make('public_url')
              ->label('Lien à partager / QR')
              ->content(fn (?DirectPaymentSetting $record): string => $record?->publicUrl()
                ?? DirectPaymentSetting::instance()->publicUrl()),
          ],
          1,
        ),
        AdminFormLayout::section(
          'Pack par défaut',
          'Formats physiques pré-sélectionnés sur la page. Laissez vide pour prendre un format par livre publié.',
          [
            CheckboxList::make('pack_book_format_ids')
              ->label('Livres du pack')
              ->options($formatOptions)
              ->columns(1)
              ->bulkToggleable()
              ->columnSpanFull(),
          ],
          1,
        ),
      ]);
  }
}
