<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Rules\CongoleseMsisdn;
use App\Services\BookReleaseNotificationService;
use App\Support\PhoneNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Inscriptions e-mail et téléphone pour la sortie d'un livre.
 */
class BookReleaseNotificationController extends Controller
{
  /**
   * Initialise le contrôleur.
   *
   * @param BookReleaseNotificationService $notificationService Service d'inscription
   */
  public function __construct(
    private readonly BookReleaseNotificationService $notificationService,
  ) {}

  /**
   * Enregistre une alerte pour un livre à paraître.
   *
   * @param Request $request Requête avec e-mail et téléphone
   * @param string $slug Slug du livre
   * @return JsonResponse Confirmation
   */
  public function store(Request $request, string $slug): JsonResponse
  {
    $validated = $request->validate([
      'email' => ['required', 'email', 'max:255'],
      'phone' => ['required', 'string', new CongoleseMsisdn()],
    ]);

    $book = Book::query()->published()->where('slug', $slug)->first();

    if ($book === null) {
      throw ValidationException::withMessages([
        'book' => ['Livre introuvable.'],
      ]);
    }

    $this->notificationService->subscribe(
      $book,
      $validated['email'],
      PhoneNormalizer::normalize($validated['phone']) ?? $validated['phone'],
    );

    return response()->json([
      'message' => 'Nous vous préviendrons dès que ce livre sera disponible.',
    ]);
  }
}
