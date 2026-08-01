<?php

namespace App\Console\Commands;

use App\Services\Mail\HostingerMailApiClient;
use Illuminate\Console\Command;
use Throwable;

/**
 * Affiche le compte Hostinger Mail API et les resourceId des boîtes.
 */
class HostingerMailInspectCommand extends Command
{
  /**
   * Signature Artisan.
   *
   * @var string
   */
  protected $signature = 'mail:hostinger-inspect
                          {--send= : Adresse de test pour un envoi API (optionnel)}';

  /**
   * Description Artisan.
   *
   * @var string
   */
  protected $description = 'Vérifie Hostinger Mail API (compte, mailboxes, envoi test optionnel)';

  /**
   * Exécute l'inspection / test d'envoi.
   *
   * @param HostingerMailApiClient $client Client API
   * @return int Code sortie
   */
  public function handle(HostingerMailApiClient $client): int
  {
    if (! $client->isConfigured()) {
      $this->error('Configure HOSTINGER_MAIL_TOKEN et HOSTINGER_MAILBOX_ID dans .env');

      return self::FAILURE;
    }

    try {
      $account = $client->getAccount();
      $this->info('Compte Hostinger Mail API OK');
      $this->line(json_encode($account, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}');

      $mailboxes = data_get($account, 'data.mailboxes', data_get($account, 'mailboxes', []));

      if (is_array($mailboxes) && $mailboxes !== []) {
        $this->newLine();
        $this->table(
          ['resourceId', 'address'],
          collect($mailboxes)->map(fn ($box): array => [
            (string) data_get($box, 'resourceId', data_get($box, 'resource_id', '')),
            (string) data_get($box, 'address', ''),
          ])->all(),
        );
        $this->comment('Copie le resourceId de la boîte d\'envoi dans HOSTINGER_MAILBOX_ID.');
      }

      try {
        $quota = $client->getStorageQuota();
        $this->newLine();
        $this->info('Quota stockage (pas le plafond d\'envois / 24 h) :');
        $this->line(json_encode($quota, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}');
      } catch (Throwable $exception) {
        $this->warn('Quota stockage indisponible : '.$exception->getMessage());
      }
    } catch (Throwable $exception) {
      $this->error($exception->getMessage());

      return self::FAILURE;
    }

    $testTo = $this->option('send');

    if (filled($testTo)) {
      try {
        $client->sendEmail([
          'to' => [(string) $testTo],
          'subject' => 'Test Hostinger Mail API — '.config('app.name'),
          'displayName' => (string) config('mail.from.name'),
          'text' => 'Envoi de test via Hostinger Mail API depuis '.config('app.url'),
          'html' => '<p>Envoi de test via <strong>Hostinger Mail API</strong> depuis '
            .e((string) config('app.url')).'</p>',
        ]);
        $this->info('E-mail de test envoyé à '.$testTo);
      } catch (Throwable $exception) {
        $this->error('Échec envoi test : '.$exception->getMessage());

        return self::FAILURE;
      }
    }

    return self::SUCCESS;
  }
}
