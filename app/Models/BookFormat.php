<?php

namespace App\Models;

use App\Enums\BookFormatType;
use App\Enums\DigitalFileType;
use App\Services\Catalog\BookFormatSkuService;
use App\Support\DigitalFilePath;
use App\Support\DigitalFormatLimits;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle représentant un format de livre (relié, ebook, audio…).
 */
class BookFormat extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'book_id',
    'type',
    'sku',
    'stock_quantity',
    'digital_file_path',
    'digital_file_type',
    'digital_max_downloads',
    'digital_stream_expiry_minutes',
    'digital_share_enabled',
    'digital_share_expiry_minutes',
    'digital_share_reading_minutes',
    'digital_share_max_links',
    'is_active',
  ];

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'type' => BookFormatType::class,
      'digital_file_type' => DigitalFileType::class,
      'digital_share_enabled' => 'boolean',
      'is_active' => 'boolean',
    ];
  }

  /**
   * Livre parent de ce format.
   *
   * @return BelongsTo<Book, $this>
   */
  public function book(): BelongsTo
  {
    return $this->belongsTo(Book::class);
  }

  /**
   * Périodes tarifaires de ce format.
   *
   * @return HasMany<PricingPeriod, $this>
   */
  public function pricingPeriods(): HasMany
  {
    return $this->hasMany(PricingPeriod::class);
  }

  /**
   * Filtre les formats actifs.
   *
   * @param \Illuminate\Database\Eloquent\Builder<BookFormat> $query Requête Eloquent
   * @return \Illuminate\Database\Eloquent\Builder<BookFormat>
   */
  public function scopeActive($query)
  {
    return $query->where('is_active', true);
  }

  /**
   * Retourne la limite de téléchargements effective pour ce format.
   *
   * @return int Nombre maximum de téléchargements
   */
  public function resolvedMaxDownloads(): int
  {
    return DigitalFormatLimits::maxDownloads($this);
  }

  /**
   * Retourne la durée de validité des liens de lecture pour ce format.
   *
   * @return int Durée en minutes
   */
  public function resolvedStreamExpiryMinutes(): int
  {
    return DigitalFormatLimits::streamExpiryMinutes($this);
  }

  /**
   * Génère automatiquement le SKU à la création si absent.
   */
  protected static function booted(): void
  {
    static::creating(function (BookFormat $format): void {
      if (filled($format->sku)) {
        return;
      }

      $format->sku = app(BookFormatSkuService::class)->generate($format);
    });

    static::saving(function (BookFormat $format): void {
      $format->digital_file_path = DigitalFilePath::normalize($format->digital_file_path);
    });
  }
}
