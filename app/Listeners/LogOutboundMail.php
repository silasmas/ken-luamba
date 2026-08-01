<?php

namespace App\Listeners;

use App\Models\MailSendLog;
use Illuminate\Mail\Events\MessageSent;
use Throwable;

/**
 * Enregistre chaque email réellement expédié pour le compteur de quota.
 */
class LogOutboundMail
{
  /**
   * Persiste un log après envoi réussi via le mailer Laravel.
   *
   * @param MessageSent $event Événement d'envoi
   */
  public function handle(MessageSent $event): void
  {
    try {
      $message = $event->message;
      $toAddresses = method_exists($message, 'getTo') ? ($message->getTo() ?? []) : [];
      $to = collect($toAddresses)
        ->map(fn ($address) => method_exists($address, 'getAddress')
          ? $address->getAddress()
          : (string) $address)
        ->filter()
        ->implode(', ');

      $subject = method_exists($message, 'getSubject') ? $message->getSubject() : null;

      MailSendLog::query()->create([
        'to' => $to !== '' ? mb_substr($to, 0, 255) : null,
        'subject' => filled($subject) ? mb_substr((string) $subject, 0, 255) : null,
        'mailer' => (string) ($event->data['mailer'] ?? config('mail.default')),
      ]);
    } catch (Throwable $exception) {
      report($exception);
    }
  }
}
