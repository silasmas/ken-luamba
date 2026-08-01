<?php

namespace App\Services\Mail;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Client HTTP pour l'API Mail Hostinger (send, account, quota stockage).
 *
 * @see https://api.mail.hostinger.com/#description/overview
 */
class HostingerMailApiClient
{
  /**
   * Indique si le token et la mailbox sont configurés.
   *
   * @return bool True si prêt à envoyer
   */
  public function isConfigured(): bool
  {
    return filled(config('services.hostinger_mail.token'))
      && filled(config('services.hostinger_mail.mailbox_id'));
  }

  /**
   * Envoie un e-mail via l'API Hostinger.
   *
   * @param array{
   *   to: list<string>,
   *   subject: string,
   *   displayName?: string|null,
   *   cc?: list<string>|null,
   *   bcc?: list<string>|null,
   *   html?: string|null,
   *   text?: string|null,
   *   attachments?: list<array<string, mixed>>|null
   * } $payload Corps V1SendRequest
   */
  public function sendEmail(array $payload): void
  {
    $mailboxId = $this->mailboxId();

    $body = array_filter([
      'to' => $payload['to'],
      'subject' => $payload['subject'],
      'displayName' => $payload['displayName'] ?? null,
      'cc' => $payload['cc'] ?? null,
      'bcc' => $payload['bcc'] ?? null,
      'html' => $payload['html'] ?? null,
      'text' => $payload['text'] ?? null,
      'attachments' => $payload['attachments'] ?? null,
    ], fn ($value) => $value !== null && $value !== []);

    try {
      $this->http()
        ->post("/api/v1/mailboxes/{$mailboxId}/send", $body)
        ->throw();
    } catch (RequestException $exception) {
      $message = $exception->response?->json('message')
        ?? $exception->response?->body()
        ?? $exception->getMessage();

      throw new RuntimeException('Échec envoi Hostinger Mail API : '.$message, 0, $exception);
    }
  }

  /**
   * Compte authentifié + mailboxes gérées.
   *
   * @return array<string, mixed> Payload /api/v1/me
   */
  public function getAccount(): array
  {
    try {
      return $this->http()->get('/api/v1/me')->throw()->json() ?? [];
    } catch (RequestException $exception) {
      throw new RuntimeException(
        'Impossible de lire le compte Hostinger Mail API : '.$exception->getMessage(),
        0,
        $exception,
      );
    }
  }

  /**
   * Quota stockage / messages IMAP (pas le plafond d'envois / 24 h).
   *
   * @return array<string, mixed> Payload /quota
   */
  public function getStorageQuota(): array
  {
    $mailboxId = $this->mailboxId();

    try {
      return $this->http()
        ->get("/api/v1/mailboxes/{$mailboxId}/quota")
        ->throw()
        ->json() ?? [];
    } catch (RequestException $exception) {
      throw new RuntimeException(
        'Impossible de lire le quota stockage Hostinger : '.$exception->getMessage(),
        0,
        $exception,
      );
    }
  }

  /**
   * Client HTTP authentifié Bearer.
   *
   * @return \Illuminate\Http\Client\PendingRequest
   */
  private function http()
  {
    $token = (string) config('services.hostinger_mail.token');

    if ($token === '') {
      throw new RuntimeException('HOSTINGER_MAIL_TOKEN manquant.');
    }

    return Http::baseUrl((string) config('services.hostinger_mail.base_url'))
      ->withToken($token)
      ->acceptJson()
      ->asJson()
      ->timeout(30);
  }

  /**
   * Resource ID de la boîte mail.
   *
   * @return string Identifiant mailbox
   */
  private function mailboxId(): string
  {
    $mailboxId = (string) config('services.hostinger_mail.mailbox_id');

    if ($mailboxId === '') {
      throw new RuntimeException('HOSTINGER_MAILBOX_ID manquant.');
    }

    return $mailboxId;
  }
}
