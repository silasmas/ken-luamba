<?php

namespace App\Http\Resources\Api\V1;

use App\Services\PricingService;
use App\Support\DigitalFormatLimits;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API pour un format de livre.
 */
class BookFormatResource extends JsonResource
{
  /**
   * Transforme le format en tableau JSON avec prix actuel.
   *
   * @param Request $request Requête HTTP entrante
   * @return array<string, mixed> Données sérialisées
   */
  public function toArray(Request $request): array
  {
    $pricingService = app(PricingService::class);
    $streamExpiryMinutes = DigitalFormatLimits::streamExpiryMinutes($this->resource);
    $shareExpiryMinutes = DigitalFormatLimits::shareLinkExpiryMinutes($this->resource);
    $shareReadingMinutes = DigitalFormatLimits::shareReadingMinutes($this->resource);

    return [
      'id' => $this->id,
      'type' => $this->type->value,
      'typeLabel' => $this->type->label(),
      'sku' => $this->sku,
      'isDigital' => $this->type->isDigital(),
      'digitalFileType' => $this->digital_file_type?->value,
      'digitalFileTypeLabel' => $this->digital_file_type?->label(),
      'digitalLimits' => $this->when($this->type->isDigital(), fn () => [
        'fileTypeLabel' => $this->digital_file_type?->label(),
        'streamExpiryMinutes' => $streamExpiryMinutes,
        'maxDownloads' => DigitalFormatLimits::maxDownloads($this->resource),
        'sharingEnabled' => DigitalFormatLimits::sharingEnabled($this->resource),
        'shareExpiryMinutes' => $shareExpiryMinutes,
        'shareReadingMinutes' => $shareReadingMinutes,
        'shareMaxLinks' => DigitalFormatLimits::shareMaxLinks($this->resource),
        'personalAccess' => true,
        'noSharing' => ! DigitalFormatLimits::sharingEnabled($this->resource),
        'summary' => DigitalFormatLimits::sharingEnabled($this->resource)
          ? 'Lecture en ligne via lien signé ('
            .DigitalFormatLimits::formatMinutesLabel($streamExpiryMinutes).'). Partage : '
            .DigitalFormatLimits::shareMaxLinks($this->resource).' lien(s), validité '
            .DigitalFormatLimits::formatMinutesLabel($shareExpiryMinutes).', lecture '
            .DigitalFormatLimits::formatMinutesLabel($shareReadingMinutes).' après ouverture.'
          : 'Lecture en ligne via lien signé ('
            .DigitalFormatLimits::formatMinutesLabel($streamExpiryMinutes).' max). Téléchargements limités à '
            .DigitalFormatLimits::maxDownloads($this->resource).' fois.',
      ]),
      'stockQuantity' => $this->stock_quantity,
      'currentPrice' => $pricingService->getCurrentPrice($this->resource),
    ];
  }
}
