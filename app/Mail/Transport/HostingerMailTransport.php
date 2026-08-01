<?php

namespace App\Mail\Transport;

use App\Services\Mail\HostingerMailApiClient;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;
use Throwable;

/**
 * Transport Laravel/Symfony qui envoie via l'API Mail Hostinger.
 */
class HostingerMailTransport extends AbstractTransport
{
  /**
   * Initialise le transport Hostinger Mail API.
   *
   * @param HostingerMailApiClient $client Client HTTP Hostinger
   */
  public function __construct(private readonly HostingerMailApiClient $client)
  {
    parent::__construct();
  }

  /**
   * Envoie le message via POST /api/v1/mailboxes/{id}/send.
   *
   * @param SentMessage $message Message Symfony à expédier
   */
  protected function doSend(SentMessage $message): void
  {
    $email = MessageConverter::toEmail($message->getOriginalMessage());

    $to = $this->addressEmails($email->getTo());
    $cc = $this->addressEmails($email->getCc());
    $bcc = $this->addressEmails($email->getBcc());

    if ($to === []) {
      throw new TransportException('Hostinger Mail API : aucun destinataire (To).');
    }

    $attachments = [];

    foreach ($email->getAttachments() as $attachment) {
      $headers = $attachment->getPreparedHeaders();
      $filename = $headers->getHeaderParameter('Content-Disposition', 'filename')
        ?: 'attachment.bin';
      $contentType = $headers->get('Content-Type')?->getBody() ?: 'application/octet-stream';

      $attachments[] = [
        'filename' => $filename,
        'content' => base64_encode($attachment->getBody()),
        'contentType' => $contentType,
        'encoding' => 'base64',
        'cid' => $attachment->hasContentId() ? $attachment->getContentId() : null,
      ];
    }

    $fromName = $email->getFrom()[0] ?? null;
    $displayName = $fromName instanceof Address
      ? ($fromName->getName() ?: (string) config('mail.from.name'))
      : (string) config('mail.from.name');

    try {
      $this->client->sendEmail([
        'to' => $to,
        'cc' => $cc !== [] ? $cc : null,
        'bcc' => $bcc !== [] ? $bcc : null,
        'displayName' => $displayName,
        'subject' => (string) ($email->getSubject() ?? ''),
        'html' => $email->getHtmlBody(),
        'text' => $email->getTextBody(),
        'attachments' => $attachments !== [] ? $attachments : null,
      ]);
    } catch (Throwable $exception) {
      throw new TransportException(
        'Hostinger Mail API : '.$exception->getMessage(),
        is_int($exception->getCode()) ? $exception->getCode() : 0,
        $exception,
      );
    }
  }

  /**
   * Extrait les adresses e-mail d'une liste Symfony Address.
   *
   * @param list<Address> $addresses Destinataires
   * @return list<string> Adresses e-mail
   */
  private function addressEmails(array $addresses): array
  {
    return array_values(array_filter(array_map(
      fn (Address $address): string => $address->getAddress(),
      $addresses,
    )));
  }

  /**
   * Identifiant du transport pour les logs Symfony.
   *
   * @return string Nom du transport
   */
  public function __toString(): string
  {
    return 'hostinger';
  }
}
