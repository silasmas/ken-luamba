<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Endpoints de déploiement (migrations en production via HTTP).
 */
class DeployController extends Controller
{
  /**
   * Point d'entrée racine : info API ou exécution des migrations en production.
   *
   * @param Request $request Requête HTTP (secret via query string)
   * @return JsonResponse|View Réponse JSON ou page d'accueil locale
   */
  public function root(Request $request): JsonResponse|View
  {
    if ($request->filled('secret') && app()->environment('production')) {
      return $this->runMigrations($request);
    }

    if (app()->environment('local')) {
      return view('welcome');
    }

    return response()->json([
      'status' => 'ok',
      'service' => 'ken-luamba-api',
      'message' => 'API Ken Luamba opérationnelle.',
    ]);
  }

  /**
   * Exécute les migrations Laravel en production (--force).
   *
   * @param Request $request Requête avec le paramètre secret
   * @return JsonResponse Résultat de la commande migrate
   */
  public function runMigrations(Request $request): JsonResponse
  {
    if (! app()->environment('production')) {
      return response()->json([
        'status' => 'error',
        'message' => 'Les migrations HTTP ne sont autorisées qu\'en production.',
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

    try {
      Artisan::call('migrate', ['--force' => true]);

      return response()->json([
        'status' => 'ok',
        'message' => 'Migrations exécutées avec succès.',
        'output' => trim(Artisan::output()),
      ]);
    } catch (\Throwable $exception) {
      report($exception);

      return response()->json([
        'status' => 'error',
        'message' => 'Échec des migrations.',
        'error' => $exception->getMessage(),
      ], 500);
    }
  }
}
