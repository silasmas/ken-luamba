<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreBookReviewRequest;
use App\Http\Resources\Api\V1\BookReviewResource;
use App\Models\Book;
use App\Models\BookReview;
use Illuminate\Http\JsonResponse;

/**
 * Contrôleur API des témoignages lecteurs.
 */
class BookReviewController extends Controller
{
  /**
   * Liste les témoignages approuvés d'un livre.
   *
   * @param string $slug Slug du livre
   * @return JsonResponse Collection JSON ou 404
   */
  public function index(string $slug): JsonResponse
  {
    $book = Book::query()->published()->where('slug', $slug)->first();

    if ($book === null) {
      return response()->json(['message' => 'Livre introuvable.'], 404);
    }

    $reviews = $book->reviews()
      ->approved()
      ->with('user')
      ->latest()
      ->get();

    $averageRating = round((float) $reviews->avg('rating'), 1);

    return response()->json([
      'data' => BookReviewResource::collection($reviews),
      'meta' => [
        'count' => $reviews->count(),
        'averageRating' => $reviews->isEmpty() ? null : $averageRating,
      ],
    ]);
  }

  /**
   * Soumet un témoignage en attente de validation admin.
   *
   * @param StoreBookReviewRequest $request Données validées
   * @param string $slug Slug du livre
   * @return JsonResponse Témoignage créé ou erreur
   */
  public function store(StoreBookReviewRequest $request, string $slug): JsonResponse
  {
    $book = Book::query()->published()->where('slug', $slug)->first();

    if ($book === null) {
      return response()->json(['message' => 'Livre introuvable.'], 404);
    }

    $user = $request->user();

    $existing = BookReview::query()
      ->where('book_id', $book->id)
      ->where('user_id', $user->id)
      ->first();

    if ($existing !== null) {
      return response()->json([
        'message' => 'Vous avez déjà soumis un témoignage pour ce livre.',
      ], 422);
    }

    $review = BookReview::query()->create([
      'book_id' => $book->id,
      'user_id' => $user->id,
      'author_role' => $request->string('authorRole')->toString() ?: null,
      'rating' => (int) $request->integer('rating'),
      'content' => $request->string('content')->toString(),
      'status' => BookReviewStatus::Pending,
    ]);

    $review->load('user');

    return response()->json([
      'message' => 'Merci ! Votre témoignage sera publié après validation par notre équipe.',
      'data' => new BookReviewResource($review),
    ], 201);
  }
}
