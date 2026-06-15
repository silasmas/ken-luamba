<?php

namespace App\Services\Invitations;

use App\Enums\InvitationDispatchChannel;
use App\Enums\InvitationDispatchStatus;
use App\Models\Event;
use App\Models\Invitation;
use App\Notifications\Invitations\EventInvitationNotification;
use App\Services\Sms\KecelSmsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

/**
 * Envoie les invitations par email, SMS (Kecel) et prépare WhatsApp.
 */
class InvitationDispatchService
{
  /**
   * Initialise le service avec ses dépendances.
   *
   * @param InvitationLinkService $linkService Service de liens invitation
   * @param InvitationMessageService $messageService Service de rendu des messages
   * @param InvitationDispatchLogger $logger Service de journalisation
   * @param KecelSmsService $kecelSmsService Client SMS Kecel
   */
  public function __construct(
    private readonly InvitationLinkService $linkService,
    private readonly InvitationMessageService $messageService,
    private readonly InvitationDispatchLogger $logger,
    private readonly KecelSmsService $kecelSmsService,
  ) {}

  /**
   * Envoie l'invitation par email.
   *
   * @param Invitation $invitation Invitation cible
   * @param string|null $messageId Identifiant du modèle de message
   * @return void
   */
  public function sendEmail(Invitation $invitation, ?string $messageId = null): void
  {
    if ($invitation->email === null || trim($invitation->email) === '') {
      throw new RuntimeException('Aucune adresse email renseignée pour cet invité.');
    }

    $body = $this->messageService->resolveBody(
      $invitation,
      InvitationDispatchChannel::Email,
      $messageId,
    );

    try {
      Notification::route('mail', $invitation->email)
        ->notify(new EventInvitationNotification($invitation, $messageId));

      $invitation->update(['email_sent_at' => now()]);

      $this->logger->log(
        $invitation,
        InvitationDispatchChannel::Email,
        $invitation->email,
        $body,
        InvitationDispatchStatus::Sent,
        $messageId,
      );
    } catch (\Throwable $exception) {
      $this->logger->log(
        $invitation,
        InvitationDispatchChannel::Email,
        $invitation->email,
        $body,
        InvitationDispatchStatus::Failed,
        $messageId,
        $exception->getMessage(),
      );

      throw new RuntimeException('Échec de l\'envoi email : '.$exception->getMessage());
    }
  }

  /**
   * Envoie l'invitation par SMS via Kecel ou ouvre l'application SMS locale.
   *
   * @param Invitation $invitation Invitation cible
   * @param string|null $messageId Identifiant du modèle de message
   * @return array{mode: string, url: string|null} Mode d'envoi et URL éventuelle
   */
  public function sendSms(Invitation $invitation, ?string $messageId = null): array
  {
    $phone = $this->normalizePhone($invitation->phone);

    if ($phone === null) {
      throw new RuntimeException('Numéro de téléphone invalide ou absent.');
    }

    $body = $this->messageService->resolveBody(
      $invitation,
      InvitationDispatchChannel::Sms,
      $messageId,
    );

    if ($this->kecelSmsService->isEnabled()) {
      $result = $this->kecelSmsService->send($phone, $body);

      if ($result->success) {
        $invitation->update(['sms_sent_at' => now()]);
      }

      $this->logger->log(
        $invitation,
        InvitationDispatchChannel::Sms,
        '+'.$phone,
        $body,
        $result->success ? InvitationDispatchStatus::Sent : InvitationDispatchStatus::Failed,
        $messageId,
        $result->rawResponse,
      );

      if (! $result->success) {
        throw new RuntimeException('Échec SMS Kecel : '.($result->message ?? $result->rawResponse));
      }

      return [
        'mode' => 'keccel',
        'url' => null,
      ];
    }

    $url = $this->linkService->smsUrl($invitation, $messageId);

    if ($url === null) {
      throw new RuntimeException('Impossible de préparer le lien SMS.');
    }

    $invitation->update(['sms_sent_at' => now()]);

    $this->logger->log(
      $invitation,
      InvitationDispatchChannel::Sms,
      '+'.$phone,
      $body,
      InvitationDispatchStatus::Sent,
      $messageId,
      'manual:sms-url',
    );

    return [
      'mode' => 'manual',
      'url' => $url,
    ];
  }

  /**
   * Envoie un lot d'invitations sur un canal donné.
   *
   * @param Collection<int, Invitation> $invitations Invitations cibles
   * @param InvitationDispatchChannel $channel Canal d'envoi
   * @param string|null $messageId Identifiant du modèle de message
   * @return array{sent: int, failed: int, urls: list<string>} Résumé de l'envoi
   */
  public function sendBulk(
    Collection $invitations,
    InvitationDispatchChannel $channel,
    ?string $messageId = null,
  ): array {
    $sent = 0;
    $failed = 0;
    $urls = [];

    foreach ($invitations as $invitation) {
      if (! $invitation instanceof Invitation) {
        continue;
      }

      try {
        match ($channel) {
          InvitationDispatchChannel::Email => $this->sendEmail($invitation, $messageId),
          InvitationDispatchChannel::Sms => $this->handleBulkSms($invitation, $messageId, $urls, $sent),
          InvitationDispatchChannel::Whatsapp => $this->handleBulkWhatsapp($invitation, $messageId, $urls, $sent),
        };

        if ($channel === InvitationDispatchChannel::Email) {
          $sent++;
        }
      } catch (RuntimeException) {
        $failed++;
      }
    }

    return compact('sent', 'failed', 'urls');
  }

  /**
   * Marque l'invitation comme envoyée via WhatsApp.
   *
   * @param Invitation $invitation Invitation cible
   * @param string|null $messageId Identifiant du modèle de message
   * @return void
   */
  public function markWhatsappSent(Invitation $invitation, ?string $messageId = null): void
  {
    $invitation->update(['whatsapp_sent_at' => now()]);

    $phone = $this->normalizePhone($invitation->phone) ?? (string) $invitation->phone;
    $body = $this->messageService->resolveBody(
      $invitation,
      InvitationDispatchChannel::Whatsapp,
      $messageId,
    );

    $this->logger->log(
      $invitation,
      InvitationDispatchChannel::Whatsapp,
      $phone !== '' ? '+'.$phone : '—',
      $body,
      InvitationDispatchStatus::Sent,
      $messageId,
      'manual:whatsapp-url',
    );
  }

  /**
   * Marque l'invitation comme envoyée via SMS (mode manuel).
   *
   * @param Invitation $invitation Invitation cible
   * @return void
   */
  public function markSmsSent(Invitation $invitation): void
  {
    $invitation->update(['sms_sent_at' => now()]);
  }

  /**
   * Retourne l'URL WhatsApp de l'invitation.
   *
   * @param Invitation $invitation Invitation cible
   * @param string|null $messageId Identifiant du modèle de message
   * @return string|null URL wa.me
   */
  public function whatsappUrl(Invitation $invitation, ?string $messageId = null): ?string
  {
    return $this->linkService->whatsappUrl($invitation, $messageId);
  }

  /**
   * Retourne l'URL SMS de l'invitation.
   *
   * @param Invitation $invitation Invitation cible
   * @param string|null $messageId Identifiant du modèle de message
   * @return string|null URL sms:
   */
  public function smsUrl(Invitation $invitation, ?string $messageId = null): ?string
  {
    return $this->linkService->smsUrl($invitation, $messageId);
  }

  /**
   * Retourne l'URL publique de l'invitation.
   *
   * @param Invitation $invitation Invitation cible
   * @return string URL frontend
   */
  public function publicUrl(Invitation $invitation): string
  {
    return $this->linkService->publicUrl($invitation);
  }

  /**
   * Indique si l'envoi SMS passe par l'API Kecel.
   *
   * @return bool True si Kecel est actif
   */
  public function usesKecelSms(): bool
  {
    return $this->kecelSmsService->isEnabled();
  }

  /**
   * Récupère le solde SMS Kecel.
   *
   * @return array{balance: string|null, raw: string, error: string|null, source: string} Solde, réponse brute, erreur et source
   */
  public function smsBalance(): array
  {
    return $this->kecelSmsService->balance();
  }

  /**
   * Traite un envoi SMS unitaire dans un lot.
   *
   * @param Invitation $invitation Invitation cible
   * @param string|null $messageId Identifiant du modèle
   * @param list<string> $urls URLs générées en mode manuel
   * @param int $sent Compteur d'envois réussis
   * @return void
   */
  private function handleBulkSms(
    Invitation $invitation,
    ?string $messageId,
    array &$urls,
    int &$sent,
  ): void {
    if (! filled($invitation->phone)) {
      throw new RuntimeException('Téléphone absent.');
    }

    $result = $this->sendSms($invitation, $messageId);

    if ($result['mode'] === 'manual' && filled($result['url'])) {
      $urls[] = $result['url'];
    }

    $sent++;
  }

  /**
   * Traite un envoi WhatsApp unitaire dans un lot.
   *
   * @param Invitation $invitation Invitation cible
   * @param string|null $messageId Identifiant du modèle
   * @param list<string> $urls URLs WhatsApp générées
   * @param int $sent Compteur d'envois réussis
   * @return void
   */
  private function handleBulkWhatsapp(
    Invitation $invitation,
    ?string $messageId,
    array &$urls,
    int &$sent,
  ): void {
    if (! filled($invitation->phone)) {
      throw new RuntimeException('Téléphone absent.');
    }

    $url = $this->whatsappUrl($invitation, $messageId);

    if ($url === null) {
      throw new RuntimeException('URL WhatsApp invalide.');
    }

    $this->markWhatsappSent($invitation, $messageId);
    $urls[] = $url;
    $sent++;
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

  /**
   * Traite les envois d'invitations programmés sur les événements.
   *
   * @return array{events:int, sent:int, failed:int} Statistiques globales
   */
  public function dispatchScheduled(): array
  {
    $events = Event::query()
      ->where('invitation_auto_send_enabled', true)
      ->whereNotNull('invitation_auto_send_at')
      ->where('invitation_auto_send_at', '<=', now())
      ->whereNull('invitation_auto_send_sent_at')
      ->get();

    $eventsCount = 0;
    $sent = 0;
    $failed = 0;

    foreach ($events as $event) {
      $channel = InvitationDispatchChannel::tryFrom((string) $event->invitation_auto_send_channel)
        ?? InvitationDispatchChannel::Email;
      $messageId = $event->invitation_auto_send_message_id;

      $invitations = $this->pendingInvitationsForChannel($event, $channel);

      if ($invitations->isEmpty()) {
        $event->update(['invitation_auto_send_sent_at' => now()]);
        continue;
      }

      if ($channel === InvitationDispatchChannel::Whatsapp) {
        $event->update(['invitation_auto_send_sent_at' => now()]);
        continue;
      }

      $result = $this->sendBulk($invitations, $channel, $messageId);
      $eventsCount++;
      $sent += $result['sent'];
      $failed += $result['failed'];
      $event->update(['invitation_auto_send_sent_at' => now()]);
    }

    return [
      'events' => $eventsCount,
      'sent' => $sent,
      'failed' => $failed,
    ];
  }

  /**
   * Retourne les invitations non encore contactées sur un canal.
   *
   * @param Event $event Événement source
   * @param InvitationDispatchChannel $channel Canal cible
   * @return Collection<int, Invitation> Invitations éligibles
   */
  private function pendingInvitationsForChannel(Event $event, InvitationDispatchChannel $channel): Collection
  {
    $query = $event->invitations();

    return match ($channel) {
      InvitationDispatchChannel::Email => $query
        ->whereNull('email_sent_at')
        ->whereNotNull('email')
        ->get(),
      InvitationDispatchChannel::Sms => $query
        ->whereNull('sms_sent_at')
        ->whereNotNull('phone')
        ->get(),
      InvitationDispatchChannel::Whatsapp => $query
        ->whereNull('whatsapp_sent_at')
        ->whereNotNull('phone')
        ->get(),
    };
  }
}
