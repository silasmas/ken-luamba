<?php

namespace App\Services;

use App\Models\DigitalAccess;
use App\Models\DigitalAccessLog;
use App\Models\DigitalAccessShare;
use App\Models\DigitalAccessShareProgress;
use App\Models\User;
use App\Support\DigitalFormatLimits;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Service de gestion des liens de partage temporaires pour contenus numériques.
 */
class DigitalShareService
{
  /**
   * Initialise le service avec le service d'accès fichiers.
   *
   * @param DigitalAccessService $digitalAccessService Service accès numériques
   */
  public function __construct(
    private readonly DigitalAccessService $digitalAccessService,
  ) {}

  /**
   * Crée un lien de partage pour un accès numérique.
   *
   * @param User $user Propriétaire de l'accès
   * @param string $accessId Identifiant d'accès
   * @param string|null $label Libellé optionnel
   * @return DigitalAccessShare Lien créé
   */
  public function createShare(User $user, string $accessId, ?string $label = null): DigitalAccessShare
  {
    $access = $this->resolveOwnedAccess($user, $accessId);
    $format = $access->bookFormat;

    if (! DigitalFormatLimits::sharingEnabled($format)) {
      throw ValidationException::withMessages([
        'share' => ['Le partage par lien n\'est pas activé pour ce format.'],
      ]);
    }

    $activeCount = $this->countActiveShares($access);

    if ($activeCount >= DigitalFormatLimits::shareMaxLinks($format)) {
      throw ValidationException::withMessages([
        'share' => ['Limite de liens de partage actifs atteinte. Révoquez un lien existant ou attendez son expiration.'],
      ]);
    }

    $linkExpiryMinutes = DigitalFormatLimits::shareLinkExpiryMinutes($format);

    $share = DigitalAccessShare::query()->create([
      'digital_access_id' => $access->id,
      'created_by_user_id' => $user->id,
      'token' => Str::random(48),
      'label' => filled($label) ? trim($label) : null,
      'expires_at' => now()->addMinutes($linkExpiryMinutes),
    ]);

    $this->logShareAction($access, $user, 'share_create', request());

    return $share->load(['digitalAccess.bookFormat.book', 'digitalAccess.orderItem']);
  }

  /**
   * Liste les liens de partage d'un accès numérique.
   *
   * @param User $user Propriétaire
   * @param string $accessId Identifiant d'accès
   * @return Collection<int, DigitalAccessShare> Liens triés par date
   */
  public function listShares(User $user, string $accessId): Collection
  {
    $access = $this->resolveOwnedAccess($user, $accessId);

    return DigitalAccessShare::query()
      ->where('digital_access_id', $access->id)
      ->with(['digitalAccess.bookFormat.book', 'digitalAccess.orderItem', 'progress'])
      ->latest('created_at')
      ->get();
  }

  /**
   * Révoque un lien de partage.
   *
   * @param User $user Propriétaire
   * @param string $accessId Identifiant d'accès
   * @param string $shareId Identifiant du lien
   * @return DigitalAccessShare Lien révoqué
   */
  public function revokeShare(User $user, string $accessId, string $shareId): DigitalAccessShare
  {
    $access = $this->resolveOwnedAccess($user, $accessId);

    $share = DigitalAccessShare::query()
      ->where('id', $shareId)
      ->where('digital_access_id', $access->id)
      ->firstOrFail();

    if ($share->revoked_at === null) {
      $share->update(['revoked_at' => now()]);
      $this->logShareAction($access, $user, 'share_revoke', request());
    }

    return $share->fresh();
  }

  /**
   * Résout un lien public par son token.
   *
   * @param string $token Token public
   * @return DigitalAccessShare Lien trouvé
   */
  public function resolveByToken(string $token): DigitalAccessShare
  {
    return DigitalAccessShare::query()
      ->where('token', $token)
      ->with(['digitalAccess.bookFormat.book', 'digitalAccess.orderItem', 'progress'])
      ->firstOrFail();
  }

  /**
   * Démarre ou reprend une session de lecture partagée.
   *
   * @param string $token Token public
   * @param Request $request Requête HTTP pour audit
   * @return array<string, mixed> État de session et progression
   */
  public function openShare(string $token, Request $request): array
  {
    $share = $this->resolveByToken($token);
    $this->assertLinkValid($share);

    if (! $share->hasReadingStarted()) {
      $readingMinutes = DigitalFormatLimits::shareReadingMinutes($share->digitalAccess?->bookFormat);

      $share->update([
        'first_opened_at' => now(),
        'reading_expires_at' => now()->addMinutes($readingMinutes),
      ]);

      $share->refresh();
      $this->logShareAction($share->digitalAccess, $share->createdBy, 'share_open', $request);
    }

    return $this->buildRecipientPayload($share->fresh(['digitalAccess.bookFormat.book', 'digitalAccess.orderItem', 'progress']));
  }

  /**
   * Retourne les métadonnées publiques d'un lien de partage (destinataire).
   *
   * @param string $token Token public
   * @return array<string, mixed> Métadonnées sérialisées
   */
  public function getPublicMetadata(string $token): array
  {
    return $this->buildRecipientPayload($this->resolveByToken($token));
  }

  /**
   * Génère l'URL signée de lecture pour un lien de partage actif.
   *
   * @param string $token Token public
   * @param Request $request Requête HTTP pour audit
   * @return array<string, mixed> URL signée et métadonnées
   */
  public function getShareStreamUrl(string $token, Request $request): array
  {
    $share = $this->resolveByToken($token);
    $this->assertReadable($share);

    $access = $share->digitalAccess;
    $format = $access?->bookFormat;

    return [
      'token' => $share->token,
      'bookTitle' => $access?->orderItem?->book_title ?? $format?->book?->title,
      'formatType' => $format?->type->value,
      'digitalFileType' => $format?->digital_file_type?->value,
      'streamUrl' => $this->buildSignedShareStreamUrl($share),
      'readingExpiresAt' => $share->reading_expires_at?->toIso8601String(),
      'readingSecondsRemaining' => $share->readingSecondsRemaining(),
      'shareReadingMinutes' => DigitalFormatLimits::shareReadingMinutes($format),
      'progress' => $this->serializeProgress($share),
    ];
  }

  /**
   * Sert le fichier via lien signé de partage.
   *
   * @param string $token Token public
   * @return \Symfony\Component\HttpFoundation\StreamedResponse Fichier streamé
   */
  public function serveShareStreamFile(string $token)
  {
    $share = $this->resolveByToken($token);
    $this->assertReadable($share);

    $access = $share->digitalAccess;
    $this->logShareAction($access, $share->createdBy, 'share_read', request());

    return $this->digitalAccessService->buildFileResponse($access, false);
  }

  /**
   * Enregistre la progression de lecture d'un lien partagé.
   *
   * @param string $token Token public
   * @param array<string, mixed> $payload Données de progression
   * @return DigitalAccessShareProgress Progression enregistrée
   */
  public function saveShareProgress(string $token, array $payload): DigitalAccessShareProgress
  {
    $share = $this->resolveByToken($token);
    $this->assertReadable($share);

    return DigitalAccessShareProgress::query()->updateOrCreate(
      ['digital_access_share_id' => $share->id],
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
   * Construit l'URL frontend publique du lien de partage.
   *
   * @param DigitalAccessShare $share Lien de partage
   * @return string URL complète
   */
  public function publicUrl(DigitalAccessShare $share): string
  {
    $frontendUrl = rtrim((string) config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3001')), '/');

    return $frontendUrl.'/lire/'.$share->token;
  }

  /**
   * Sérialise un lien pour l'API propriétaire.
   *
   * @param DigitalAccessShare $share Lien de partage
   * @param DigitalAccess $access Accès associé
   * @return array<string, mixed> Données API
   */
  public function serializeForOwner(DigitalAccessShare $share, DigitalAccess $access): array
  {
    $format = $access->bookFormat;
    $maxLinks = DigitalFormatLimits::shareMaxLinks($format);

    return [
      'id' => $share->id,
      'token' => $share->token,
      'label' => $share->label,
      'shareUrl' => $this->publicUrl($share),
      'linkExpiresAt' => $share->expires_at?->toIso8601String(),
      'linkSecondsRemaining' => $share->linkSecondsRemaining(),
      'shareLinkExpiryMinutes' => DigitalFormatLimits::shareLinkExpiryMinutes($format),
      'shareReadingMinutes' => DigitalFormatLimits::shareReadingMinutes($format),
      'hasReadingStarted' => $share->hasReadingStarted(),
      'readingExpiresAt' => $share->reading_expires_at?->toIso8601String(),
      'readingSecondsRemaining' => $share->readingSecondsRemaining(),
      'isActive' => $share->isActive(),
      'isExpired' => ! $share->isLinkValid(),
      'isReadingExpired' => $share->hasReadingStarted() && ! $share->isReadingActive(),
      'canRead' => $share->canRead(),
      'isRevoked' => $share->revoked_at !== null,
      'revokedAt' => $share->revoked_at?->toIso8601String(),
      'createdAt' => $share->created_at?->toIso8601String(),
      'remainingShareLinks' => max(0, $maxLinks - $this->countActiveShares($access)),
      'maxShareLinks' => $maxLinks,
    ];
  }

  /**
   * Génère une URL signée temporaire liée au token de partage.
   *
   * @param DigitalAccessShare $share Lien actif
   * @return string URL signée absolue
   */
  public function buildSignedShareStreamUrl(DigitalAccessShare $share): string
  {
    $expiresAt = $this->resolveStreamSignatureExpiry($share);

    return URL::temporarySignedRoute(
      'library.share-stream-file',
      $expiresAt,
      ['token' => $share->token],
      absolute: true,
    );
  }

  /**
   * Compte les liens de partage encore actifs pour un accès.
   *
   * @param DigitalAccess $access Accès numérique
   * @return int Nombre de liens actifs
   */
  public function countActiveShares(DigitalAccess $access): int
  {
    return DigitalAccessShare::query()
      ->where('digital_access_id', $access->id)
      ->active()
      ->count();
  }

  /**
   * Construit la réponse publique destinée au lecteur (sans infos d'expiration du lien).
   *
   * @param DigitalAccessShare $share Lien cible
   * @return array<string, mixed> Données destinataire
   */
  private function buildRecipientPayload(DigitalAccessShare $share): array
  {
    $access = $share->digitalAccess;
    $format = $access?->bookFormat;
    $isUnavailable = $share->revoked_at !== null || ! $share->isLinkValid();

    return [
      'token' => $share->token,
      'bookTitle' => $access?->orderItem?->book_title ?? $format?->book?->title,
      'bookSubtitle' => $format?->book?->subtitle,
      'coverImage' => MediaUrl::fromPath($format?->book?->cover_image),
      'formatType' => $format?->type->value,
      'formatLabel' => $format?->type->label(),
      'digitalFileType' => $format?->digital_file_type?->value,
      'digitalFileTypeLabel' => $format?->digital_file_type?->label(),
      'shareReadingMinutes' => DigitalFormatLimits::shareReadingMinutes($format),
      'hasReadingStarted' => $share->hasReadingStarted(),
      'readingSecondsRemaining' => $share->hasReadingStarted()
        ? $share->readingSecondsRemaining()
        : 0,
      'isReadingExpired' => $share->hasReadingStarted() && ! $share->isReadingActive(),
      'canRead' => $share->canRead(),
      'isUnavailable' => $isUnavailable,
      'hasFile' => filled($format?->digital_file_path),
      'progress' => $this->serializeProgress($share),
    ];
  }

  /**
   * Construit la réponse publique complète d'un lien partagé (propriétaire).
   *
   * @param DigitalAccessShare $share Lien cible
   * @return array<string, mixed> Données publiques
   */
  private function buildPublicPayload(DigitalAccessShare $share): array
  {
    $access = $share->digitalAccess;
    $format = $access?->bookFormat;

    return [
      'token' => $share->token,
      'label' => $share->label,
      'shareUrl' => $this->publicUrl($share),
      'bookTitle' => $access?->orderItem?->book_title ?? $format?->book?->title,
      'bookSubtitle' => $format?->book?->subtitle,
      'coverImage' => MediaUrl::fromPath($format?->book?->cover_image),
      'formatType' => $format?->type->value,
      'formatLabel' => $format?->type->label(),
      'digitalFileType' => $format?->digital_file_type?->value,
      'digitalFileTypeLabel' => $format?->digital_file_type?->label(),
      'linkExpiresAt' => $share->expires_at?->toIso8601String(),
      'linkSecondsRemaining' => $share->linkSecondsRemaining(),
      'shareLinkExpiryMinutes' => DigitalFormatLimits::shareLinkExpiryMinutes($format),
      'shareReadingMinutes' => DigitalFormatLimits::shareReadingMinutes($format),
      'hasReadingStarted' => $share->hasReadingStarted(),
      'readingExpiresAt' => $share->reading_expires_at?->toIso8601String(),
      'readingSecondsRemaining' => $share->readingSecondsRemaining(),
      'isLinkValid' => $share->isLinkValid(),
      'isReadingActive' => $share->isReadingActive(),
      'canRead' => $share->canRead(),
      'isActive' => $share->canRead(),
      'isExpired' => ! $share->isLinkValid(),
      'isReadingExpired' => $share->hasReadingStarted() && ! $share->isReadingActive(),
      'isRevoked' => $share->revoked_at !== null,
      'hasFile' => filled($format?->digital_file_path),
      'progress' => $this->serializeProgress($share),
      'secondsRemaining' => $share->secondsRemaining(),
    ];
  }

  /**
   * Sérialise la progression d'un lien partagé.
   *
   * @param DigitalAccessShare $share Lien cible
   * @return array<string, mixed>|null Progression ou null
   */
  private function serializeProgress(DigitalAccessShare $share): ?array
  {
    $progress = $share->relationLoaded('progress') ? $share->progress : $share->progress()->first();

    if ($progress === null) {
      return null;
    }

    return [
      'progressPercent' => $progress->progress_percent,
      'epubCfi' => $progress->epub_cfi,
      'audioPositionSeconds' => $progress->audio_position_seconds,
      'audioDurationSeconds' => $progress->audio_duration_seconds,
      'lastOpenedAt' => $progress->last_opened_at?->toIso8601String(),
    ];
  }

  /**
   * Détermine l'expiration de la signature du flux partagé.
   *
   * @param DigitalAccessShare $share Lien cible
   * @return Carbon Date d'expiration
   */
  private function resolveStreamSignatureExpiry(DigitalAccessShare $share): Carbon
  {
    if ($share->reading_expires_at instanceof Carbon && $share->reading_expires_at->isFuture()) {
      return $share->reading_expires_at;
    }

    return $share->expires_at;
  }

  /**
   * Vérifie que l'URL du lien est encore valide.
   *
   * @param DigitalAccessShare $share Lien cible
   * @return void
   */
  private function assertLinkValid(DigitalAccessShare $share): void
  {
    if ($share->revoked_at !== null) {
      throw ValidationException::withMessages([
        'share' => ['Ce lien de partage a été révoqué.'],
      ]);
    }

    if ($share->expires_at?->isPast()) {
      throw ValidationException::withMessages([
        'share' => ['Ce contenu n\'est plus disponible. Contactez la personne qui vous l\'a partagé.'],
      ]);
    }
  }

  /**
   * Vérifie que la lecture est encore autorisée.
   *
   * @param DigitalAccessShare $share Lien cible
   * @return void
   */
  private function assertReadable(DigitalAccessShare $share): void
  {
    $this->assertLinkValid($share);

    if (! $share->hasReadingStarted()) {
      throw ValidationException::withMessages([
        'share' => ['Lancez d\'abord la lecture pour accéder au contenu.'],
      ]);
    }

    if (! $share->isReadingActive()) {
      throw ValidationException::withMessages([
        'share' => ['Le temps de lecture alloué est écoulé.'],
      ]);
    }
  }

  /**
   * Résout un accès appartenant à l'utilisateur.
   *
   * @param User $user Client connecté
   * @param string $accessId Identifiant d'accès
   * @return DigitalAccess Accès validé
   */
  private function resolveOwnedAccess(User $user, string $accessId): DigitalAccess
  {
    return DigitalAccess::query()
      ->where('id', $accessId)
      ->where('user_id', $user->id)
      ->where('is_active', true)
      ->with(['bookFormat.book', 'orderItem'])
      ->firstOrFail();
  }

  /**
   * Journalise une action liée au partage.
   *
   * @param DigitalAccess $access Accès concerné
   * @param User|null $user Utilisateur source
   * @param string $action Type d'action
   * @param Request $request Requête HTTP
   * @return void
   */
  private function logShareAction(DigitalAccess $access, ?User $user, string $action, Request $request): void
  {
    DigitalAccessLog::query()->create([
      'digital_access_id' => $access->id,
      'user_id' => $user?->id,
      'action' => $action,
      'ip_address' => $request->ip(),
      'user_agent' => $request->userAgent(),
      'accessed_at' => now(),
    ]);
  }
}
