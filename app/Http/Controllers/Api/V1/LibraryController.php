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
    $result = $this->digitalAccessService->getStreamUrl(
      $request->user(),
      $accessId,
      $request,
    );

    return response()->json(['data' => $result]);
  }
}
