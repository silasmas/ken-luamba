<?php

namespace App\Notifications\Invitations;

use App\Enums\InvitationDispatchChannel;
use App\Models\Invitation;
use App\Services\Invitations\InvitationLinkService;
use App\Services\Invitations\InvitationMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email d'invitation à un événement Ken Luamba.
 */
class EventInvitationNotification extends Notification
{
  use Queueable;

  /**
   * Initialise la notification d'invitation.
   *
   * @param Invitation $invitation Invitation cible
   * @param string|null $messageId Identifiant du modèle de message choisi
   */
  public function __construct(
    private readonly Invitation $invitation,
    private readonly ?string $messageId = null,
  ) {}

  /**
   * Canaux de diffusion.
   *
   * @param mixed $notifiable Destinataire
   * @return list<string>
   */
  public function via(mixed $notifiable): array
  {
    return ['mail'];
  }

  /**
   * Construit l'email d'invitation.
   *
   * @param mixed $notifiable Destinataire
   * @return MailMessage Message email
   */
  public function toMail(mixed $notifiable): MailMessage
  {
    $this->invitation->loadMissing('event');
    $messageService = app(InvitationMessageService::class);
    $linkService = app(InvitationLinkService::class);
    $invitationUrl = $linkService->publicUrl($this->invitation);
    $subject = $messageService->resolveEmailSubject($this->invitation, $this->messageId);
    $body = $messageService->resolveBody(
      $this->invitation,
      InvitationDispatchChannel::Email,
      $this->messageId,
    );

    $lines = array_values(array_filter(
      explode("\n", $body),
      fn (string $line): bool => trim($line) !== '',
    ));

    $mail = (new MailMessage)
      ->subject($subject)
      ->greeting('Bonjour '.$this->invitation->full_name.',');

    foreach ($lines as $index => $line) {
      if ($index === 0 && str_starts_with($line, 'Bonjour')) {
        continue;
      }

      if (str_contains($line, $invitationUrl)) {
        continue;
      }

      $mail->line($line);
    }

    return $mail
      ->action('Confirmer ma présence', $invitationUrl)
      ->line('Merci de répondre via le lien pour confirmer votre présence et laisser un mot.');
  }
}
