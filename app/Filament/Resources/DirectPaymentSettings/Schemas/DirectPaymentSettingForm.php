<?php

namespace App\Filament\Resources\DirectPaymentSettings\Schemas;

use App\Enums\BookFormatType;
use App\Filament\Support\AdminFormLayout;
use App\Models\BookFormat;
use App\Models\DirectPaymentSetting;
use App\Services\DirectPaymentQrService;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

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
          'Lien et QR à partager pour ouvrir la page paiement direct (frontend).',
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
              ->label('Lien à partager')
              ->content(fn (?DirectPaymentSetting $record): string => $record?->publicUrl()
                ?? DirectPaymentSetting::instance()->publicUrl()),
            Placeholder::make('public_qr')
              ->label('QR code')
              ->content(function (): HtmlString {
                $qrUrl = app(DirectPaymentQrService::class)->imageUrl();
                $downloadUrl = $qrUrl.'?size=800&download=1';

                return new HtmlString(
                  '<div style="display:flex;flex-direction:column;gap:0.75rem;align-items:flex-start;">'
                  .'<img src="'.e($qrUrl).'" alt="QR paiement direct" width="220" height="220" '
                  .'style="border:1px solid #e5e7eb;border-radius:8px;background:#fff;padding:8px;" />'
                  .'<a href="'.e($downloadUrl).'" target="_blank" rel="noopener" '
                  .'style="color:#2563eb;text-decoration:underline;font-size:0.875rem;">'
                  .'Télécharger le QR (PNG)</a>'
                  .'<span style="color:#6b7280;font-size:0.8rem;">Scannez ce QR pour ouvrir la page paiement direct.</span>'
                  .'</div>'
                );
              })
              ->columnSpanFull(),
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
