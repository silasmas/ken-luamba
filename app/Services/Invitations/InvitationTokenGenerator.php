<?php

namespace App\Services\Invitations;

use App\Models\Invitation;
use Illuminate\Support\Str;

/**
 * Génère des tokens publics courts et uniques pour les invitations.
 */
class InvitationTokenGenerator
{
  /**
   * Retourne la longueur configurée des tokens.
   *
   * @return int Nombre de caractères
   */
  public function length(): int
  {
    $length = (int) config('invitations.token_length', 10);

    return max(8, min($length, 32));
  }

  /**
   * Génère un token unique pour une nouvelle invitation.
   *
   * @return string Token URL-safe
   */
  public function generateUnique(): string
  {
    $length = $this->length();

    do {
      $token = Str::random($length);
    } while (Invitation::query()->where('token', $token)->exists());

    return $token;
  }

  /**
   * Remplace un token trop long par un token court (économie SMS).
   *
   * @param Invitation $invitation Invitation à mettre à jour si nécessaire
   * @return void
   */
  public function ensureShortToken(Invitation $invitation): void
  {
    $targetLength = $this->length();
    $currentToken = (string) $invitation->token;

    if ($currentToken === '' || strlen($currentToken) <= $targetLength) {
      return;
    }

    $invitation->update([
      'token' => $this->generateUnique(),
    ]);

    $invitation->refresh();
  }
}
