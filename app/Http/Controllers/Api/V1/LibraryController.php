<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DigitalAccessResource;
use App\Services\DigitalAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Contrôleur API pour la bibliothèque numérique client.
 */
class LibraryController extends Controller
{
  /**
   * Initialise le contrôleur bibliothèque.
   *
   * @param DigitalAccessService $digitalAccessService Service accès numériques
   */
  public function __construct(
    private readonly DigitalAccessService $digitalAccessService,
  ) {}

  /**
   * Liste les contenus numériques accessibles par le client.
   *
   * @param Request $request Requête authentifiée
   * @return AnonymousResourceCollection Bibliothèque
   */
  public function index(Request $request): AnonymousResourceCollection
  {
    $items = $this->digitalAccessService->listForUser($request->user());

    return DigitalAccessResource::collection($items);
  }

  /**
   * Génère une URL signée de lecture/streaming.
   *
   * @param Request $request Requête authentifiée
   * @param string $accessId Identifiant d'accès
   * @return JsonResponse URL et métadonnées
   */
  public function stream(Request $request, string $accessId): JsonResponse
  {
    $mode = $request->string('mode', 'read')->toString();

    if (! in_array($mode, ['read', 'download'], true)) {
      $mode = 'read';
    }

    $result = $this->digitalAccessService->getStreamUrl(
      $request->user(),
      $accessId,
      $request,
      $mode,
    );

    return response()->json(['data' => $result]);
  }

  /**
   * Sert le fichier numérique authentifié (lecture ou téléchargement).
   *
   * @param Request $request Requête authentifiée
   * @param string $accessId Identifiant d'accès
   * @return \Symfony\Component\HttpFoundation\StreamedResponse Fichier streamé
   */
  public function file(Request $request, string $accessId)
  {
    $mode = $request->string('mode', 'download')->toString();

    if ($mode !== 'download') {
      abort(403, 'La lecture en ligne nécessite un lien signé temporaire. Rouvrez le livre depuis Ma bibliothèque.');
    }

    $access = $this->digitalAccessService->authorizeFileAccess(
      $request->user(),
      $accessId,
      $request,
      'download',
    );

    return $this->digitalAccessService->buildFileResponse($access, true);
  }

  /**
   * Sert le fichier via URL signée temporaire (lecture en ligne).
   *
   * @param string $accessId Identifiant d'accès
   * @param int $userId Identifiant utilisateur
   * @return \Symfony\Component\HttpFoundation\StreamedResponse Fichier streamé
   */
  public function signedFile(string $accessId, int $userId)
  {
    return $this->digitalAccessService->serveSignedStreamFile($accessId, $userId);
  }

  /**
   * Enregistre la progression de lecture ou d'écoute.
   *
   * @param Request $request Requête authentifiée
   * @param string $accessId Identifiant d'accès
   * @return JsonResponse Progression enregistrée
   */
  public function saveProgress(Request $request, string $accessId): JsonResponse
  {
    $validated = $request->validate([
      'progressPercent' => ['nullable', 'integer', 'min:0', 'max:100'],
      'epubCfi' => ['nullable', 'string', 'max:5000'],
      'audioPositionSeconds' => ['nullable', 'integer', 'min:0'],
      'audioDurationSeconds' => ['nullable', 'integer', 'min:0'],
    ]);

    $progress = $this->digitalAccessService->saveReadingProgress(
      $request->user(),
      $accessId,
      $validated,
    );

    return response()->json([
      'data' => [
        'accessId' => $progress->digital_access_id,
        'progressPercent' => $progress->progress_percent,
        'epubCfi' => $progress->epub_cfi,
        'audioPositionSeconds' => $progress->audio_position_seconds,
        'audioDurationSeconds' => $progress->audio_duration_seconds,
        'lastOpenedAt' => $progress->last_opened_at?->toIso8601String(),
      ],
    ]);
  }
}
