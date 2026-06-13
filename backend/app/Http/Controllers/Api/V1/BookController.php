<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BookResource;
use App\Http\Resources\Api\V1\BookSummaryResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur API pour le catalogue de livres.
 */
class BookController extends Controller
{
  /**
   * Liste les livres publiés avec pagination.
   *
   * @param Request $request Requête avec filtres optionnels
   * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection Collection paginée
   */
  public function index(Request $request)
  {
    $query = Book::query()
      ->published()
      ->with([
        'author',
        'formats' => fn ($formatsQuery) => $formatsQuery->active()->with('pricingPeriods'),
      ])
      ->orderBy('sort_order');

    if ($request->boolean('featured')) {
      $query->where('is_featured', true);
    }

    if ($request->filled('author')) {
      $query->whereHas('author', fn ($authorQuery) => $authorQuery->where('slug', $request->string('author')));
    }

    return BookSummaryResource::collection(
      $query->paginate($request->integer('per_page', 12))
    );
  }

  /**
   * Retourne le détail d'un livre publié par slug.
   *
   * @param string $slug Identifiant URL du livre
   * @return BookResource|JsonResponse Détail ou 404
   */
  public function show(string $slug): BookResource|JsonResponse
  {
    $book = Book::query()
      ->published()
      ->where('slug', $slug)
      ->with([
        'author',
        'formats' => fn ($formatsQuery) => $formatsQuery->active()->with('pricingPeriods'),
      ])
      ->first();

    if ($book === null) {
      return response()->json([
        'message' => 'Livre introuvable.',
      ], 404);
    }

    return new BookResource($book);
  }
}
