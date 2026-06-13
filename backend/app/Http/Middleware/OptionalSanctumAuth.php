<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authentifie l'utilisateur via Bearer token sans exiger la connexion.
 */
class OptionalSanctumAuth
{
  /**
   * Résout l'utilisateur Sanctum si un token valide est fourni.
   *
   * @param Request $request Requête HTTP
   * @param Closure $next Suite du pipeline
   * @return Response Réponse HTTP
   */
  public function handle(Request $request, Closure $next): Response
  {
    $token = $request->bearerToken();

    if ($token !== null && $token !== '') {
      $accessToken = PersonalAccessToken::findToken($token);

      if ($accessToken !== null) {
        $user = $accessToken->tokenable;
        $request->setUserResolver(fn () => $user);
      }
    }

    return $next($request);
  }
}
