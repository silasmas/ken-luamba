<?php

namespace App\Http\Resources\Api\V1;

use App\Support\DigitalFilePath;
use App\Support\DigitalFormatLimits;
use App\Support\MediaUrl;
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
    $format = $this->bookFormat;
    $maxDownloads = DigitalFormatLimits::maxDownloads($format);
    $streamExpiryHours = DigitalFormatLimits::streamExpiryHours($format);

    return [
      'id' => $this->id,
      'bookTitle' => $this->orderItem?->book_title ?? $this->bookFormat?->book?->title,
      'bookSlug' => $this->bookFormat?->book?->slug,
      'bookSubtitle' => $this->bookFormat?->book?->subtitle,
      'coverImage' => MediaUrl::fromPath($this->bookFormat?->book?->cover_image),
      'formatType' => $this->bookFormat?->type->value,
      'formatLabel' => $this->bookFormat?->type->label(),
      'digitalFileType' => $this->bookFormat?->digital_file_type?->value,
      'digitalFileTypeLabel' => $this->bookFormat?->digital_file_type?->label(),
      'orderNumber' => $this->order?->order_number,
      'grantedAt' => $this->granted_at?->toIso8601String(),
      'lastAccessedAt' => $this->logs->first()?->accessed_at?->toIso8601String(),
      'hasFile' => DigitalFilePath::existsOnDisk($this->bookFormat?->digital_file_path),
      'downloadCount' => $this->download_count,
      'maxDownloads' => $maxDownloads,
      'remainingDownloads' => max(0, $maxDownloads - $this->download_count),
      'streamExpiryHours' => $streamExpiryHours,
      'progressPercent' => $this->readingProgress?->progress_percent ?? 0,
      'epubCfi' => $this->readingProgress?->epub_cfi,
      'audioPositionSeconds' => $this->readingProgress?->audio_position_seconds,
      'audioDurationSeconds' => $this->readingProgress?->audio_duration_seconds,
      'readingLastOpenedAt' => $this->readingProgress?->last_opened_at?->toIso8601String(),
    ];
  }
}
