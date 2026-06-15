<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BookSummaryResource;
use App\Models\Book;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Liste de favoris des utilisateurs connectés.
 */
class WishlistController extends Controller
{
  /**
   * Initialise le contrôleur.
   *
   * @param WishlistService $wishlistService Service favoris
   */
  public function __construct(
    private readonly WishlistService $wishlistService,
  ) {}

  /**
   * Retourne les slugs des livres favoris de l'utilisateur.
   *
   * @param Request $request Requête authentifiée
   * @return JsonResponse Liste des slugs
   */
  public function index(Request $request): JsonResponse
  {
    $user = $request->user();
    $books = $this->wishlistService->booksFor($user);

    return response()->json([
      'data' => [
        'bookSlugs' => $books->pluck('slug')->filter()->values()->all(),
        'books' => BookSummaryResource::collection($books),
      ],
    ]);
  }

  /**
   * Ajoute ou retire un livre des favoris.
   *
   * @param Request $request Requête authentifiée
   * @param string $bookId Identifiant du livre
   * @return JsonResponse État après bascule
   */
  public function toggle(Request $request, string $bookId): JsonResponse
  {
    $user = $request->user();
    $book = Book::query()->published()->find($bookId);

    if ($book === null) {
      throw ValidationException::withMessages([
        'bookId' => ['Livre introuvable.'],
      ]);
    }

    $isInWishlist = $this->wishlistService->toggle($user, $book);

    return response()->json([
      'data' => [
        'bookSlug' => $book->slug,
        'isInWishlist' => $isInWishlist,
      ],
    ]);
  }
}
