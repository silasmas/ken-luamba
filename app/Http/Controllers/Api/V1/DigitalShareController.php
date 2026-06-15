<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DigitalShareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur API pour les liens de partage de contenus numériques.
 */
class DigitalShareController extends Controller
{
  /**
   * Initialise le contrôleur avec le service de partage.
   *
   * @param DigitalShareService $digitalShareService Service partage
   */
  public function __construct(
    private readonly DigitalShareService $digitalShareService,
  ) {}

  /**
   * Crée un lien de partage pour un accès bibliothèque.
   *
   * @param Request $request Requête authentifiée
   * @param string $accessId Identifiant d'accès
   * @return JsonResponse Lien créé
   */
  public function store(Request $request, string $accessId): JsonResponse
  {
    $validated = $request->validate([
      'label' => ['nullable', 'string', 'max:120'],
    ]);

    $share = $this->digitalShareService->createShare(
      $request->user(),
      $accessId,
      $validated['label'] ?? null,
    );

    $access = $share->digitalAccess;

    return response()->json([
      'data' => $this->digitalShareService->serializeForOwner($share, $access),
    ], 201);
  }

  /**
   * Liste les liens de partage d'un accès bibliothèque.
   *
   * @param Request $request Requête authentifiée
   * @param string $accessId Identifiant d'accès
   * @return JsonResponse Liste des liens
   */
  public function index(Request $request, string $accessId): JsonResponse
  {
    $shares = $this->digitalShareService->listShares($request->user(), $accessId);

    return response()->json([
      'data' => $shares->map(
        fn ($share) => $this->digitalShareService->serializeForOwner($share, $share->digitalAccess),
      )->values(),
    ]);
  }

  /**
   * Révoque un lien de partage.
   *
   * @param Request $request Requête authentifiée
   * @param string $accessId Identifiant d'accès
   * @param string $shareId Identifiant du lien
   * @return JsonResponse Lien révoqué
   */
  public function destroy(Request $request, string $accessId, string $shareId): JsonResponse
  {
    $share = $this->digitalShareService->revokeShare(
      $request->user(),
      $accessId,
      $shareId,
    );

    return response()->json([
      'data' => $this->digitalShareService->serializeForOwner($share, $share->digitalAccess),
    ]);
  }

  /**
   * Démarre ou reprend la session de lecture partagée.
   *
   * @param Request $request Requête publique
   * @param string $token Token public
   * @return JsonResponse État de session
   */
  public function open(Request $request, string $token): JsonResponse
  {
    return response()->json([
      'data' => $this->digitalShareService->openShare($token, $request),
    ]);
  }

  /**
   * Enregistre la progression de lecture d'un lien partagé.
   *
   * @param Request $request Requête publique
   * @param string $token Token public
   * @return JsonResponse Progression enregistrée
   */
  public function saveProgress(Request $request, string $token): JsonResponse
  {
    $validated = $request->validate([
      'progressPercent' => ['nullable', 'integer', 'min:0', 'max:100'],
      'epubCfi' => ['nullable', 'string', 'max:5000'],
      'audioPositionSeconds' => ['nullable', 'integer', 'min:0'],
      'audioDurationSeconds' => ['nullable', 'integer', 'min:0'],
    ]);

    $progress = $this->digitalShareService->saveShareProgress($token, $validated);

    return response()->json([
      'data' => [
        'progressPercent' => $progress->progress_percent,
        'epubCfi' => $progress->epub_cfi,
        'audioPositionSeconds' => $progress->audio_position_seconds,
        'audioDurationSeconds' => $progress->audio_duration_seconds,
        'lastOpenedAt' => $progress->last_opened_at?->toIso8601String(),
      ],
    ]);
  }

  /**
   * Retourne les métadonnées publiques d'un lien de partage.
   *
   * @param string $token Token public
   * @return JsonResponse Métadonnées
   */
  public function show(string $token): JsonResponse
  {
    return response()->json([
      'data' => $this->digitalShareService->getPublicMetadata($token),
    ]);
  }

  /**
   * Retourne l'URL signée de lecture pour un lien de partage actif.
   *
   * @param Request $request Requête publique
   * @param string $token Token public
   * @return JsonResponse URL signée
   */
  public function stream(Request $request, string $token): JsonResponse
  {
    return response()->json([
      'data' => $this->digitalShareService->getShareStreamUrl($token, $request),
    ]);
  }

  /**
   * Sert le fichier via URL signée de partage.
   *
   * @param string $token Token public
   * @return \Symfony\Component\HttpFoundation\StreamedResponse Fichier streamé
   */
  public function signedFile(string $token)
  {
    return $this->digitalShareService->serveShareStreamFile($token);
  }
}
