<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API pour un accès à contenu numérique.
 */
class DigitalAccessResource extends JsonResource
{
  /**
   * Transforme l'accès en tableau JSON.
   *
   * @param Request $request Requête HTTP entrante
   * @return array<string, mixed> Données sérialisées
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'bookTitle' => $this->orderItem?->book_title ?? $this->bookFormat?->book?->title,
      'bookSlug' => $this->bookFormat?->book?->slug,
      'formatType' => $this->bookFormat?->type->value,
      'formatLabel' => $this->bookFormat?->type->label(),
      'digitalFileType' => $this->bookFormat?->digital_file_type?->value,
      'digitalFileTypeLabel' => $this->bookFormat?->digital_file_type?->label(),
      'orderNumber' => $this->order?->order_number,
      'grantedAt' => $this->granted_at?->toIso8601String(),
      'hasFile' => ! empty($this->bookFormat?->digital_file_path),
      'downloadCount' => $this->download_count,
      'maxDownloads' => (int) config('digital.max_downloads', 5),
      'remainingDownloads' => max(0, (int) config('digital.max_downloads', 5) - $this->download_count),
      'streamExpiryHours' => (int) config('digital.stream_expiry_hours', 2),
    ];
  }
}
