<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Endpoints de déploiement (migrations et seeders en production via HTTP).
 */
class DeployController extends Controller
{
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

    return response()->json([
      'status' => 'ok',
      'service' => 'ken-luamba-api',
      'message' => 'API Ken Luamba opérationnelle.',
      'deploy' => [
        'migrate' => '/?secret=VOTRE_SECRET',
        'seed' => '/?secret=VOTRE_SECRET&action=seed',
        'setup' => '/?secret=VOTRE_SECRET&action=setup',
      ],
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
      return match ($action) {
        'migrate' => $this->runMigrations(),
        'seed' => $this->runSeeders(),
        'setup' => $this->runSetup(),
        default => response()->json([
          'status' => 'error',
          'message' => 'Action inconnue. Utilisez migrate, seed ou setup.',
        ], 400),
      };
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
   * Lance les migrations Laravel (--force).
   *
   * @return JsonResponse Résultat migrate
   */
  private function runMigrations(): JsonResponse
  {
    Artisan::call('migrate', ['--force' => true]);

    return response()->json([
      'status' => 'ok',
      'action' => 'migrate',
      'message' => 'Migrations exécutées avec succès.',
      'output' => trim(Artisan::output()),
    ]);
  }

  /**
   * Lance les seeders Laravel (--force).
   *
   * @return JsonResponse Résultat db:seed
   */
  private function runSeeders(): JsonResponse
  {
    Artisan::call('db:seed', ['--force' => true]);

    return response()->json([
      'status' => 'ok',
      'action' => 'seed',
      'message' => 'Seeders exécutés avec succès.',
      'output' => trim(Artisan::output()),
    ]);
  }

  /**
   * Exécute migrations puis seeders (équivalent setup local initial).
   *
   * @return JsonResponse Résultat combiné
   */
  private function runSetup(): JsonResponse
  {
    Artisan::call('migrate', ['--force' => true]);
    $migrateOutput = trim(Artisan::output());

    Artisan::call('db:seed', ['--force' => true]);
    $seedOutput = trim(Artisan::output());

    return response()->json([
      'status' => 'ok',
      'action' => 'setup',
      'message' => 'Migrations et seeders exécutés avec succès.',
      'output' => [
        'migrate' => $migrateOutput,
        'seed' => $seedOutput,
      ],
    ]);
  }
}
