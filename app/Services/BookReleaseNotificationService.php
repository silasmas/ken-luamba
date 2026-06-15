<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookReleaseSubscription;
use Illuminate\Validation\ValidationException;

/**
 * Gère les inscriptions e-mail pour la sortie d'un livre.
 */
class BookReleaseNotificationService
{
  /**
   * Enregistre une demande d'alerte pour un livre publié.
   *
   * @param Book $book Livre cible
   * @param string $email Adresse e-mail du visiteur
   * @return BookReleaseSubscription Inscription créée ou existante
   */
  public function subscribe(Book $book, string $email): BookReleaseSubscription
  {
    $normalizedEmail = strtolower(trim($email));

    if ($normalizedEmail === '') {
      throw ValidationException::withMessages([
        'email' => ['Adresse e-mail requise.'],
      ]);
    }

    return BookReleaseSubscription::query()->firstOrCreate(
      [
        'book_id' => $book->id,
        'email' => $normalizedEmail,
      ],
    );
  }
}
