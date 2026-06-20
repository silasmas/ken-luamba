<?php

namespace App\Services\Invitations;

use App\Models\Event;
use App\Support\MediaUrl;

/**
 * Résout l'URL d'aperçu social (Open Graph / WhatsApp) pour un événement.
 */
class InvitationShareImageService
{
  /**
   * Retourne l'URL absolue de l'image à utiliser pour l'aperçu d'un lien d'invitation.
   *
   * @param Event|null $event Événement source
   * @return string URL HTTPS absolue (couverture ou logo)
   */
  public function urlForEvent(?Event $event): string
  {
    $event?->loadMissing('books');

    foreach ($event?->books ?? [] as $book) {
      $coverUrl = MediaUrl::fromPath($book->cover_image);

      if ($coverUrl !== null && $coverUrl !== '') {
        return $coverUrl;
      }
    }

    $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');

    return $frontendUrl.'/images/logo-kl.png';
  }
}
