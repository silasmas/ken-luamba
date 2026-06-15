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
        'streamExpiryHours' => DigitalFormatLimits::streamExpiryHours($this->resource),
        'maxDownloads' => DigitalFormatLimits::maxDownloads($this->resource),
        'personalAccess' => true,
        'noSharing' => true,
        'summary' => 'Accès personnel lié à votre compte. Lecture en ligne recommandée. Téléchargements limités à '
          .DigitalFormatLimits::maxDownloads($this->resource).' fois.',
      ]),
      'stockQuantity' => $this->stock_quantity,
      'currentPrice' => $pricingService->getCurrentPrice($this->resource),
    ];
  }
}
