<?php

namespace App\Services;

use App\Models\DigitalAccess;
use App\Models\DigitalAccessLog;
use App\Models\DigitalReadingProgress;
use App\Models\Order;
use App\Models\User;
use App\Support\DigitalFilePath;
use App\Support\DigitalFormatLimits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Service de gestion des accès aux contenus numériques (ebook, audio).
 */
class DigitalAccessService
{
  /**
   * Accorde les droits numériques pour une commande payée.
   *
   * @param Order $order Commande payée
   * @return void
   */
  public function grantForOrder(Order $order): void
  {
    $order->loadMissing(['items', 'user']);

    foreach ($order->items as $item) {
      if (! $item->format_type->isDigital()) {
        continue;
      }

      DigitalAccess::query()->updateOrCreate(
        [
          'user_id' => $order->user_id,
          'order_item_id' => $item->id,
        ],
        [
          'order_id' => $order->id,
          'book_format_id' => $item->book_format_id,
          'is_active' => true,
          'granted_at' => now(),
          'expires_at' => null,
        ],
      );
    }
  }

  /**
   * Liste la bibliothèque numérique d'un utilisateur.
   *
   * @param User $user Client connecté
   * @return \Illuminate\Database\Eloquent\Collection<int, DigitalAccess> Accès actifs
   */
  public function listForUser(User $user)
  {
    return DigitalAccess::query()
      ->where('user_id', $user->id)
      ->where('is_active', true)
      ->with([
        'bookFormat.book',
        'orderItem',
        'readingProgress',
        'logs' => fn ($query) => $query->latest('accessed_at')->limit(1),
      ])
      ->latest('granted_at')
      ->get();
  }

  /**
   * Génère une URL signée temporaire pour lire un contenu numérique.
   *
   * @param User $user Client connecté
   * @param string $accessId Identifiant de l'accès
   * @param Request $request Requête HTTP pour audit
   * @param string $mode Mode d'accès : read (lecture) ou download (téléchargement)
   * @return array<string, mixed> URL de lecture et métadonnées
   */
  public function getStreamUrl(User $user, string $accessId, Request $request, string $mode = 'read'): array
  {
    $access = $this->authorizeFileAccess($user, $accessId, $request, $mode);

    $streamUrl = rtrim((string) config('app.url'), '/')
      .'/api/v1/library/'.$access->id.'/file?mode='.$mode;

    $format = $access->bookFormat;
    $maxDownloads = DigitalFormatLimits::maxDownloads($format);
    $streamExpiryHours = DigitalFormatLimits::streamExpiryHours($format);

    return [
      'accessId' => $access->id,
      'bookTitle' => $access->orderItem?->book_title ?? $format?->book?->title,
      'formatType' => $format?->type->value,
      'formatLabel' => $format?->type->label(),
      'digitalFileType' => $format?->digital_file_type?->value,
      'digitalFileTypeLabel' => $format?->digital_file_type?->label(),
      'streamUrl' => $streamUrl,
      'watermark' => $user->email.' — '.$access->order_id,
      'expiresInMinutes' => $streamExpiryHours * 60,
      'downloadCount' => $access->download_count,
      'maxDownloads' => $maxDownloads,
      'remainingDownloads' => max(0, $maxDownloads - $access->download_count),
    ];
  }

  /**
   * Valide l'accès, journalise et applique les limites de téléchargement.
   *
   * @param User $user Client connecté
   * @param string $accessId Identifiant de l'accès
   * @param Request $request Requête HTTP pour audit
   * @param string $mode Mode d'accès : read ou download
   * @return DigitalAccess Accès autorisé
   */
  public function authorizeFileAccess(User $user, string $accessId, Request $request, string $mode = 'read'): DigitalAccess
  {
    $access = DigitalAccess::query()
      ->where('id', $accessId)
      ->where('user_id', $user->id)
      ->where('is_active', true)
      ->with(['bookFormat.book', 'orderItem'])
      ->firstOrFail();

    $filePath = DigitalFilePath::normalize($access->bookFormat?->digital_file_path);

    if ($filePath === null) {
      throw ValidationException::withMessages([
        'access' => ['Fichier numérique non disponible pour ce format.'],
      ]);
    }

    if (! Storage::disk('local')->exists($filePath)) {
      throw ValidationException::withMessages([
        'access' => ['Fichier introuvable sur le serveur. Uploadez le fichier dans l\'admin (Formats du livre).'],
      ]);
    }

    $maxDownloads = DigitalFormatLimits::maxDownloads($access->bookFormat);
    $isDownload = $mode === 'download';

    if ($isDownload && $access->download_count >= $maxDownloads) {
      throw ValidationException::withMessages([
        'access' => ['Limite de téléchargements atteinte ('.$maxDownloads.' max). Contactez le support.'],
      ]);
    }

    if ($isDownload) {
      $access->increment('download_count');
      $access->refresh();
    }

    $this->logAccess($access, $user, $isDownload ? 'download' : 'read', $request);

    return $access;
  }

  /**
   * Sert le fichier numérique via URL signée (route web legacy).
   *
   * @param string $accessId Identifiant accès
   * @param int $userId Identifiant utilisateur
   * @return \Symfony\Component\HttpFoundation\StreamedResponse Fichier streamé
   */
  public function streamFile(string $accessId, int $userId)
  {
    $access = DigitalAccess::query()
      ->where('id', $accessId)
      ->where('user_id', $userId)
      ->where('is_active', true)
      ->with('bookFormat')
      ->firstOrFail();

    return $this->buildFileResponse($access);
  }

  /**
   * Sert le fichier numérique pour un utilisateur authentifié (API).
   *
   * @param User $user Client connecté
   * @param string $accessId Identifiant accès
   * @return \Symfony\Component\HttpFoundation\StreamedResponse Fichier streamé
   */
  public function serveAuthenticatedFile(User $user, string $accessId, string $mode = 'read')
  {
    $access = DigitalAccess::query()
      ->where('id', $accessId)
      ->where('user_id', $user->id)
      ->where('is_active', true)
      ->with('bookFormat.book')
      ->firstOrFail();

    return $this->buildFileResponse($access, $mode === 'download');
  }

  /**
   * Construit la réponse HTTP pour un fichier numérique.
   *
   * @param DigitalAccess $access Accès validé
   * @return \Symfony\Component\HttpFoundation\StreamedResponse Fichier streamé
   */
  public function buildFileResponse(DigitalAccess $access, bool $asDownload = false)
  {
    $filePath = DigitalFilePath::normalize($access->bookFormat?->digital_file_path);

    if ($filePath === null || ! Storage::disk('local')->exists($filePath)) {
      abort(404, 'Fichier introuvable.');
    }

    $mimeType = $access->bookFormat?->digital_file_type?->mimeTypes()[0]
      ?? Storage::disk('local')->mimeType($filePath)
      ?? 'application/octet-stream';

    if ($mimeType === 'application/octet-stream') {
      $mimeType = self::guessMimeTypeFromPath($filePath) ?? $mimeType;
    }

    $extension = $access->bookFormat?->digital_file_type?->extensions()[0] ?? 'bin';
    $bookTitle = $access->bookFormat?->book?->title ?? 'livre';
    $downloadName = preg_replace('/[^\pL\pN\-_ ]/u', '', $bookTitle) ?: 'livre';
    $downloadName = trim($downloadName).'.'.$extension;

    $headers = [
      'Content-Type' => $mimeType,
      'Cache-Control' => 'private, no-store',
      'Access-Control-Expose-Headers' => 'Content-Disposition, Content-Type, Content-Length',
    ];

    if ($asDownload) {
      $headers['Content-Disposition'] = 'attachment; filename="'.$downloadName.'"';
    }

    return Storage::disk('local')->response($filePath, $asDownload ? $downloadName : null, $headers);
  }

  /**
   * Enregistre ou met à jour la progression de lecture d'un contenu.
   *
   * @param User $user Client connecté
   * @param string $accessId Identifiant de l'accès
   * @param array<string, mixed> $payload Données de progression
   * @return DigitalReadingProgress Progression enregistrée
   */
  public function saveReadingProgress(User $user, string $accessId, array $payload): DigitalReadingProgress
  {
    $access = DigitalAccess::query()
      ->where('id', $accessId)
      ->where('user_id', $user->id)
      ->where('is_active', true)
      ->firstOrFail();

    return DigitalReadingProgress::query()->updateOrCreate(
      [
        'digital_access_id' => $access->id,
        'user_id' => $user->id,
      ],
      [
        'progress_percent' => min(100, max(0, (int) ($payload['progressPercent'] ?? 0))),
        'epub_cfi' => $payload['epubCfi'] ?? null,
        'audio_position_seconds' => isset($payload['audioPositionSeconds'])
          ? max(0, (int) $payload['audioPositionSeconds'])
          : null,
        'audio_duration_seconds' => isset($payload['audioDurationSeconds'])
          ? max(0, (int) $payload['audioDurationSeconds'])
          : null,
        'last_opened_at' => now(),
      ],
    );
  }

  /**
   * Devine le type MIME à partir de l'extension du fichier.
   *
   * @param string $filePath Chemin relatif du fichier
   * @return string|null Type MIME détecté
   */
  private static function guessMimeTypeFromPath(string $filePath): ?string
  {
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    return match ($extension) {
      'epub' => 'application/epub+zip',
      'pdf' => 'application/pdf',
      'mp3' => 'audio/mpeg',
      default => null,
    };
  }

  /**
   * Enregistre un log d'accès numérique.
   *
   * @param DigitalAccess $access Accès consulté
   * @param User $user Utilisateur
   * @param string $action Type d'action
   * @param Request $request Requête HTTP
   * @return void
   */
  private function logAccess(DigitalAccess $access, User $user, string $action, Request $request): void
  {
    DigitalAccessLog::query()->create([
      'digital_access_id' => $access->id,
      'user_id' => $user->id,
      'action' => $action,
      'ip_address' => $request->ip(),
      'user_agent' => $request->userAgent(),
      'accessed_at' => now(),
    ]);
  }
}
