<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookReleaseSubscription;
use App\Models\User;
use App\Support\PhoneNormalizer;
use Illuminate\Validation\ValidationException;

/**
 * Gère les inscriptions e-mail et téléphone pour la sortie d'un livre.
 */
class BookReleaseNotificationService
{
  /**
   * Enregistre une demande d'alerte pour un livre publié.
   *
   * @param Book $book Livre cible
   * @param string $email Adresse e-mail du visiteur
   * @param string $phone Numéro MSISDN 243XXXXXXXXX
   * @return BookReleaseSubscription Inscription créée ou mise à jour
   */
  public function subscribe(Book $book, string $email, string $phone): BookReleaseSubscription
  {
    $normalizedEmail = strtolower(trim($email));
    $normalizedPhone = PhoneNormalizer::normalize($phone);

    if ($normalizedEmail === '') {
      throw ValidationException::withMessages([
        'email' => ['Adresse e-mail requise.'],
      ]);
    }

    if (! PhoneNormalizer::isValid($normalizedPhone)) {
      throw ValidationException::withMessages([
        'phone' => ['Numéro invalide. Utilisez le format 243XXXXXXXXX (12 chiffres).'],
      ]);
    }

    $subscription = BookReleaseSubscription::query()->firstOrCreate(
      [
        'book_id' => $book->id,
        'email' => $normalizedEmail,
      ],
      [
        'phone' => $normalizedPhone,
      ],
    );

    if ($subscription->phone !== $normalizedPhone) {
      $subscription->update(['phone' => $normalizedPhone]);
    }

    $this->enrichUserPhoneFromSubscription($normalizedEmail, $normalizedPhone);

    return $subscription->fresh();
  }

  /**
   * Complète le téléphone principal d'un compte existant si absent.
   *
   * @param string $email E-mail de l'inscription
   * @param string $phone Téléphone normalisé
   * @return void
   */
  private function enrichUserPhoneFromSubscription(string $email, string $phone): void
  {
    $user = User::query()->where('email', $email)->first();

    if ($user === null || filled($user->phone)) {
      return;
    }

    $user->forceFill(['phone' => $phone])->save();
  }
}
