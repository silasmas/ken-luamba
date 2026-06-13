<?php

namespace App\Filament\Resources\BookReviews;

use App\Filament\Resources\BookReviews\Pages\EditBookReview;
use App\Filament\Resources\BookReviews\Pages\ListBookReviews;
use App\Filament\Resources\BookReviews\Tables\BookReviewsTable;
use App\Models\BookReview;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BookReviewResource extends Resource
{
  protected static ?string $model = BookReview::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

  protected static ?string $navigationLabel = 'Témoignages';

  protected static ?string $modelLabel = 'Témoignage';

  protected static ?string $pluralModelLabel = 'Témoignages';

  protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

  protected static ?int $navigationSort = 3;

  /**
   * Les avis sont soumis par les lecteurs en ligne.
   *
   * @return bool False pour masquer la création manuelle
   */
  public static function canCreate(): bool
  {
    return false;
  }

  public static function table(Table $table): Table
  {
    return BookReviewsTable::configure($table);
  }

  public static function getPages(): array
  {
    return [
      'index' => ListBookReviews::route('/'),
      'edit' => EditBookReview::route('/{record}/edit'),
    ];
  }
}
