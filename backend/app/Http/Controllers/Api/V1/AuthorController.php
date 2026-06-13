<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AuthorResource;
use App\Models\Author;
use Illuminate\Http\JsonResponse;

/**
 * Contrôleur API pour les auteurs.
 */
class AuthorController extends Controller
{
  /**
   * Retourne le profil public d'un auteur par slug.
   *
   * @param string $slug Identifiant URL de l'auteur
   * @return AuthorResource|JsonResponse Profil ou 404
   */
  public function show(string $slug): AuthorResource|JsonResponse
  {
    $author = Author::query()
      ->published()
      ->where('slug', $slug)
      ->with([
        'books' => fn ($query) => $query
          ->published()
          ->orderBy('sort_order')
          ->with([
            'author',
            'formats' => fn ($formatsQuery) => $formatsQuery->active()->with('pricingPeriods'),
          ]),
      ])
      ->first();

    if ($author === null) {
      return response()->json([
        'message' => 'Auteur introuvable.',
      ], 404);
    }

    return new AuthorResource($author);
  }
}
