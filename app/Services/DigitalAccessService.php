<?php

namespace App\Services;

use App\Models\DigitalAccess;
use App\Models\DigitalAccessLog;
use App\Models\DigitalReadingProgress;
use App\Models\Order;
use App\Models\User;
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
    $access = DigitalAccess::query()
      ->where('id', $accessId)
      ->where('user_id', $user->id)
      ->where('is_active', true)
      ->with(['bookFormat.book', 'orderItem'])
      ->firstOrFail();

    $filePath = $access->bookFormat?->digital_file_path;

    if ($filePath === null || $filePath === '') {
      throw ValidationException::withMessages([
        'access' => ['Fichier numérique non disponible pour ce format.'],
      ]);
    }

    if (! Storage::disk('local')->exists($filePath)) {
      throw ValidationException::withMessages([
        'access' => ['Fichier introuvable sur le serveur.'],
      ]);
    }

    $maxDownloads = (int) config('digital.max_downloads', 5);
    $isDownload = $mode === 'download';

    if ($isDownload && $access->download_count >= $maxDownloads) {
      throw ValidationException::withMessages([
        'access' => ['Limite de téléchargements atteinte ('.$maxDownloads.' max). Contactez le support.'],
      ]);
    }

    if ($isDownload) {
      $access->increment('download_count');
    }

    $this->logAccess($access, $user, $isDownload ? 'download' : 'read', $request);

    $streamUrl = rtrim((string) config('app.url'), '/')
      .'/api/v1/library/'.$access->id.'/file?mode='.$mode;

    return [
      'accessId' => $access->id,
      'bookTitle' => $access->orderItem?->book_title ?? $access->bookFormat?->book?->title,
      'formatType' => $access->bookFormat?->type->value,
      'formatLabel' => $access->bookFormat?->type->label(),
      'digitalFileType' => $access->bookFormat?->digital_file_type?->value,
      'digitalFileTypeLabel' => $access->bookFormat?->digital_file_type?->label(),
      'streamUrl' => $streamUrl,
      'watermark' => $user->email.' — '.$access->order_id,
      'expiresInMinutes' => (int) config('digital.stream_expiry_hours', 2) * 60,
      'downloadCount' => $access->download_count,
      'maxDownloads' => (int) config('digital.max_downloads', 5),
      'remainingDownloads' => max(0, (int) config('digital.max_downloads', 5) - $access->download_count),
    ];
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
  public function serveAuthenticatedFile(User $user, string $accessId)
  {
    $access = DigitalAccess::query()
      ->where('id', $accessId)
      ->where('user_id', $user->id)
      ->where('is_active', true)
      ->with('bookFormat')
      ->firstOrFail();

    return $this->buildFileResponse($access);
  }

  /**
   * Construit la réponse HTTP pour un fichier numérique.
   *
   * @param DigitalAccess $access Accès validé
   * @return \Symfony\Component\HttpFoundation\StreamedResponse Fichier streamé
   */
  private function buildFileResponse(DigitalAccess $access)
  {
    $filePath = $access->bookFormat?->digital_file_path;

    if ($filePath === null || ! Storage::disk('local')->exists($filePath)) {
      abort(404, 'Fichier introuvable.');
    }

    $mimeType = $access->bookFormat?->digital_file_type?->mimeTypes()[0]
      ?? Storage::disk('local')->mimeType($filePath)
      ?? 'application/octet-stream';

    return Storage::disk('local')->response($filePath, null, [
      'Content-Type' => $mimeType,
      'Cache-Control' => 'private, no-store',
    ]);
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
