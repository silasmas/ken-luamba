<?php

namespace App\Services;

use App\Models\DigitalAccess;
use App\Models\DigitalAccessLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
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
      ->with(['bookFormat.book', 'orderItem'])
      ->latest('granted_at')
      ->get();
  }

  /**
   * Génère une URL signée temporaire pour lire un contenu numérique.
   *
   * @param User $user Client connecté
   * @param string $accessId Identifiant de l'accès
   * @param Request $request Requête HTTP pour audit
   * @return array<string, mixed> URL de lecture et métadonnées
   */
  public function getStreamUrl(User $user, string $accessId, Request $request): array
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

    if ($access->download_count >= $maxDownloads) {
      throw ValidationException::withMessages([
        'access' => ['Limite de téléchargements atteinte ('.$maxDownloads.' max). Contactez le support.'],
      ]);
    }

    $access->increment('download_count');
    $this->logAccess($access, $user, 'stream', $request);

    $signedUrl = URL::temporarySignedRoute(
      'digital.stream',
      now()->addHours((int) config('digital.stream_expiry_hours', 2)),
      ['accessId' => $access->id, 'userId' => $user->id],
    );

    return [
      'accessId' => $access->id,
      'bookTitle' => $access->orderItem?->book_title ?? $access->bookFormat?->book?->title,
      'formatType' => $access->bookFormat?->type->value,
      'formatLabel' => $access->bookFormat?->type->label(),
      'digitalFileType' => $access->bookFormat?->digital_file_type?->value,
      'digitalFileTypeLabel' => $access->bookFormat?->digital_file_type?->label(),
      'streamUrl' => $signedUrl,
      'watermark' => $user->email.' — '.$access->order_id,
      'expiresInMinutes' => (int) config('digital.stream_expiry_hours', 2) * 60,
      'downloadCount' => $access->download_count,
      'maxDownloads' => (int) config('digital.max_downloads', 5),
      'remainingDownloads' => max(0, (int) config('digital.max_downloads', 5) - $access->download_count),
    ];
  }

  /**
   * Sert le fichier numérique via URL signée.
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

    $filePath = $access->bookFormat?->digital_file_path;

    if ($filePath === null || ! Storage::disk('local')->exists($filePath)) {
      abort(404, 'Fichier introuvable.');
    }

    return Storage::disk('local')->response($filePath);
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
