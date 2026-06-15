<?php

namespace App\Http\Controllers;

use App\Services\Deploy\DeployService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints de déploiement (migrations et seeders en production via HTTP).
 */
class DeployController extends Controller
{
  /**
   * Initialise le contrôleur avec le service de déploiement.
   *
   * @param DeployService $deployService Service central des tâches deploy
   */
  public function __construct(
    private readonly DeployService $deployService,
  ) {}

  /**
   * Point d'entrée racine : info API ou tâches de déploiement en production.
   *
   * @param Request $request Requête HTTP (secret et action via query string)
   * @return JsonResponse|View Réponse JSON ou page d'accueil locale
   */
  public function root(Request $request): JsonResponse|View
  {
    if ($request->filled('secret') && app()->environment('production')) {
      return $this->runDeployAction($request);
    }

    if (app()->environment('local')) {
      return view('welcome');
    }

    return view('admin-portal', [
      'adminLoginUrl' => url('/admin/login'),
      'frontendUrl' => config('app.frontend_url'),
    ]);
  }

  /**
   * Exécute une action de déploiement autorisée en production.
   *
   * @param Request $request Requête avec secret et action
   * @return JsonResponse Résultat de l'action demandée
   */
  public function runDeployAction(Request $request): JsonResponse
  {
    $secretError = $this->validateDeploySecret($request);

    if ($secretError !== null) {
      return $secretError;
    }

    $action = strtolower((string) $request->query('action', 'migrate'));

    try {
      $result = match ($action) {
        'migrate' => $this->deployService->migrate(),
        'seed' => $this->resolveSeedAction($request),
        'setup' => $this->deployService->setup(),
        'shield' => $this->deployService->shield(),
        'storage' => $this->deployService->storageLink(),
        default => [
          'status' => 'error',
          'message' => 'Action inconnue. Utilisez migrate, seed, setup, shield ou storage.',
        ],
      };

      if (isset($result['status']) && $result['status'] === 'error') {
        return response()->json($result, 400);
      }

      return response()->json([
        'status' => 'ok',
        ...$result,
      ]);
    } catch (\Throwable $exception) {
      report($exception);

      return response()->json([
        'status' => 'error',
        'message' => 'Échec de l\'action de déploiement.',
        'action' => $action,
        'error' => $exception->getMessage(),
      ], 500);
    }
  }

  /**
   * Valide le secret de déploiement pour les requêtes HTTP.
   *
   * @param Request $request Requête entrante
   * @return JsonResponse|null Erreur JSON ou null si valide
   */
  private function validateDeploySecret(Request $request): ?JsonResponse
  {
    if (! app()->environment('production')) {
      return response()->json([
        'status' => 'error',
        'message' => 'Les actions de déploiement HTTP ne sont autorisées qu\'en production.',
      ], 403);
    }

    $deploySecret = (string) config('app.deploy_secret', '');

    if ($deploySecret === '') {
      return response()->json([
        'status' => 'error',
        'message' => 'DEPLOY_SECRET non configuré sur le serveur.',
      ], 503);
    }

    $providedSecret = (string) $request->query('secret', '');

    if (! hash_equals($deploySecret, $providedSecret)) {
      return response()->json([
        'status' => 'error',
        'message' => 'Secret de déploiement invalide.',
      ], 403);
    }

    return null;
  }

  /**
   * Résout l'action seed : tous les seeders ou une sélection via query string.
   *
   * @param Request $request Requête avec class ou classes
   * @return array{action: string, message: string, output: string|array<string, string>}
   */
  private function resolveSeedAction(Request $request): array
  {
    $classParam = (string) $request->query('class', '');
    $classesParam = (string) $request->query('classes', '');

    if ($classParam === '' && $classesParam === '') {
      return $this->deployService->seed();
    }

    $keys = [];

    if ($classParam !== '') {
      $keys[] = $classParam;
    }

    if ($classesParam !== '') {
      foreach (explode(',', $classesParam) as $key) {
        $trimmed = trim($key);

        if ($trimmed !== '') {
          $keys[] = $trimmed;
        }
      }
    }

    return $this->deployService->seedSelected($keys);
  }
}
