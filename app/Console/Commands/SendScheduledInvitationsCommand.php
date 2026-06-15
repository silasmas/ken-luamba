<?php

namespace App\Console\Commands;

use App\Services\Invitations\InvitationDispatchService;
use Illuminate\Console\Command;

/**
 * Envoie les rappels d'invitation programmés sur les événements.
 */
class SendScheduledInvitationsCommand extends Command
{
  /**
   * Signature de la commande Artisan.
   *
   * @var string
   */
  protected $signature = 'invitations:dispatch-scheduled';

  /**
   * Description affichée dans la liste des commandes.
   *
   * @var string
   */
  protected $description = 'Envoie les rappels d\'invitation programmés';

  /**
   * Exécute les envois programmés.
   *
   * @param InvitationDispatchService $dispatchService Service d'envoi
   * @return int Code de sortie
   */
  public function handle(InvitationDispatchService $dispatchService): int
  {
    $result = $dispatchService->dispatchScheduled();

    $this->info(
      $result['events'].' événement(s), '
      .$result['sent'].' envoi(s), '
      .$result['failed'].' échec(s).',
    );

    return self::SUCCESS;
  }
}
