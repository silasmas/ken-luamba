<?php

namespace App\Console\Commands;

use App\Services\BookRelease\BookReleaseDispatchService;
use Illuminate\Console\Command;

/**
 * Envoie automatiquement les alertes sortie programmées.
 */
class SendScheduledReleaseNotificationsCommand extends Command
{
  /**
   * Signature de la commande Artisan.
   *
   * @var string
   */
  protected $signature = 'release-notifications:dispatch-scheduled';

  /**
   * Description affichée dans la liste des commandes.
   *
   * @var string
   */
  protected $description = 'Envoie les alertes sortie programmées aux inscrits';

  /**
   * Exécute les envois programmés.
   *
   * @param BookReleaseDispatchService $dispatchService Service d'envoi
   * @return int Code de sortie
   */
  public function handle(BookReleaseDispatchService $dispatchService): int
  {
    $result = $dispatchService->dispatchScheduled();

    $this->info(
      'Livres traités : '.$result['books']
      .' | Envoyés : '.$result['sent']
      .' | Échecs : '.$result['failed'],
    );

    return self::SUCCESS;
  }
}
