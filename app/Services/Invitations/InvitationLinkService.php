<?php

namespace App\Services\Invitations;

use App\Enums\InvitationDispatchChannel;
use App\Models\Invitation;
use Illuminate\Support\Carbon;

/**
 * Construit les liens et messages d'invitation.
 */
class InvitationLinkService
{
  /**
   * Initialise le service avec le moteur de modèles de messages.
   *
   * @param InvitationMessageService $messageService Service de rendu des messages
   */
  public function __construct(
    private readonly InvitationMessageService $messageService,
  ) {}

  /**
   * Retourne l'URL publique de réponse à l'invitation.
   *
   * @param Invitation $invitation Invitation cible
   * @return string URL frontend complète
   */
  public function publicUrl(Invitation $invitation): string
  {
    app(InvitationTokenGenerator::class)->ensureShortToken($invitation);

    return $this->buildPublicUrl((string) $invitation->token);
  }

  /**
   * Construit l'URL publique courte à partir d'un token.
   *
   * @param string $token Token d'invitation
   * @return string URL frontend (ex. https://kenluamba.com/i/abc12XY9z0)
   */
  public function buildPublicUrl(string $token): string
  {
    $frontendUrl = rtrim((string) config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3001')), '/');
    $publicPath = trim((string) config('invitations.public_path', 'i'), '/');

    return $frontendUrl.'/'.$publicPath.'/'.trim($token, '/');
  }

  /**
   * Compose le message texte par défaut (sans modèle personnalisé).
   *
   * @param Invitation $invitation Invitation cible
   * @return string Message personnalisé
   */
  public function defaultMessageBody(Invitation $invitation): string
  {
    $invitation->loadMissing('event');
    $event = $invitation->event;
    $date = $event?->starts_at instanceof Carbon
      ? $event->starts_at->timezone(config('app.timezone'))->locale('fr')->isoFormat('dddd D MMMM YYYY [à] HH[h]mm')
      : '';

    $lines = [
      'Bonjour '.$invitation->full_name.',',
      '',
      'Ken Luamba a le plaisir de vous inviter à : '.$event?->title,
    ];

    if ($date !== '') {
      $lines[] = 'Date : '.$date;
    }

    if ($event?->location) {
      $lines[] = 'Lieu : '.$event->location;
    }

    $lines[] = '';
    $lines[] = 'Merci de confirmer votre présence via le lien ci-dessous :';
    $lines[] = $this->publicUrl($invitation);

    return implode("\n", $lines);
  }

  /**
   * Compose le message texte envoyé par email, WhatsApp ou SMS.
   *
   * @param Invitation $invitation Invitation cible
   * @param InvitationDispatchChannel $channel Canal d'envoi
   * @param string|null $messageId Identifiant du modèle choisi
   * @return string Message personnalisé
   */
  public function messageBody(
    Invitation $invitation,
    InvitationDispatchChannel $channel,
    ?string $messageId = null,
  ): string {
    return $this->messageService->resolveBody($invitation, $channel, $messageId);
  }

  /**
   * Retourne l'URL WhatsApp avec message prérempli.
   *
   * @param Invitation $invitation Invitation cible
   * @param string|null $messageId Identifiant du modèle choisi
   * @return string|null URL wa.me ou null si téléphone absent
   */
  public function whatsappUrl(Invitation $invitation, ?string $messageId = null): ?string
  {
    $phone = $this->normalizePhone($invitation->phone);

    if ($phone === null) {
      return null;
    }

    return 'https://wa.me/'.$phone.'?text='.rawurlencode(
      $this->messageBody($invitation, InvitationDispatchChannel::Whatsapp, $messageId),
    );
  }

  /**
   * Retourne l'URL SMS avec message prérempli.
   *
   * @param Invitation $invitation Invitation cible
   * @param string|null $messageId Identifiant du modèle choisi
   * @return string|null URL sms: ou null si téléphone absent
   */
  public function smsUrl(Invitation $invitation, ?string $messageId = null): ?string
  {
    $phone = $this->normalizePhone($invitation->phone);

    if ($phone === null) {
      return null;
    }

    return 'sms:+'.$phone.'?body='.rawurlencode(
      $this->messageBody($invitation, InvitationDispatchChannel::Sms, $messageId),
    );
  }

  /**
   * Normalise un numéro de téléphone pour WhatsApp/SMS.
   *
   * @param string|null $phone Numéro brut
   * @return string|null Numéro international sans +
   */
  private function normalizePhone(?string $phone): ?string
  {
    if ($phone === null || trim($phone) === '') {
      return null;
    }

    $digits = preg_replace('/\D+/', '', $phone) ?? '';

    if ($digits === '') {
      return null;
    }

    if (str_starts_with($digits, '0') && strlen($digits) === 10) {
      $digits = '243'.substr($digits, 1);
    }

    if (str_starts_with($digits, '00')) {
      $digits = substr($digits, 2);
    }

    return $digits;
  }
}
