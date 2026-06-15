<?php

namespace App\Services;

use App\Models\DigitalAccess;
use App\Models\DigitalAccessLog;
use App\Models\DigitalAccessShare;
use App\Models\User;
use App\Support\DigitalFormatLimits;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
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

    $expiryHours = DigitalFormatLimits::shareExpiryHours($format);

    $share = DigitalAccessShare::query()->create([
      'digital_access_id' => $access->id,
      'created_by_user_id' => $user->id,
      'token' => Str::random(48),
      'label' => filled($label) ? trim($label) : null,
      'expires_at' => now()->addHours($expiryHours),
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
      ->with(['digitalAccess.bookFormat.book', 'digitalAccess.orderItem'])
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
      ->with(['digitalAccess.bookFormat.book', 'digitalAccess.orderItem'])
      ->firstOrFail();
  }

  /**
   * Retourne les métadonnées publiques d'un lien de partage.
   *
   * @param string $token Token public
   * @return array<string, mixed> Métadonnées sérialisées
   */
  public function getPublicMetadata(string $token): array
  {
    $share = $this->resolveByToken($token);
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
      'expiresAt' => $share->expires_at?->toIso8601String(),
      'secondsRemaining' => $share->secondsRemaining(),
      'shareExpiryHours' => DigitalFormatLimits::shareExpiryHours($format),
      'isActive' => $share->isActive(),
      'isExpired' => $share->expires_at?->isPast() ?? true,
      'isRevoked' => $share->revoked_at !== null,
      'hasFile' => filled($format?->digital_file_path),
    ];
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
    $this->assertShareActive($share);

    $access = $share->digitalAccess;
    $format = $access?->bookFormat;

    return [
      'token' => $share->token,
      'bookTitle' => $access?->orderItem?->book_title ?? $format?->book?->title,
      'formatType' => $format?->type->value,
      'digitalFileType' => $format?->digital_file_type?->value,
      'streamUrl' => $this->buildSignedShareStreamUrl($share),
      'expiresAt' => $share->expires_at?->toIso8601String(),
      'secondsRemaining' => $share->secondsRemaining(),
      'shareExpiryHours' => DigitalFormatLimits::shareExpiryHours($format),
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
    $this->assertShareActive($share);

    $access = $share->digitalAccess;
    $this->logShareAction($access, $share->createdBy, 'share_read', request());

    return $this->digitalAccessService->buildFileResponse($access, false);
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
      'expiresAt' => $share->expires_at?->toIso8601String(),
      'secondsRemaining' => $share->secondsRemaining(),
      'shareExpiryHours' => DigitalFormatLimits::shareExpiryHours($format),
      'isActive' => $share->isActive(),
      'isExpired' => $share->expires_at?->isPast() ?? true,
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
    return URL::temporarySignedRoute(
      'library.share-stream-file',
      $share->expires_at,
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
   * Vérifie qu'un lien est encore utilisable.
   *
   * @param DigitalAccessShare $share Lien cible
   * @return void
   */
  private function assertShareActive(DigitalAccessShare $share): void
  {
    if ($share->revoked_at !== null) {
      throw ValidationException::withMessages([
        'share' => ['Ce lien de partage a été révoqué.'],
      ]);
    }

    if ($share->expires_at?->isPast()) {
      throw ValidationException::withMessages([
        'share' => ['Ce lien de partage a expiré. Demandez un nouveau lien au propriétaire.'],
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
