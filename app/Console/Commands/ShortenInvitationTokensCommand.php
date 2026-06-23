<?php

namespace App\Console\Commands;

use App\Models\Invitation;
use App\Services\Invitations\InvitationTokenGenerator;
use Illuminate\Console\Command;

/**
 * Raccourcit les tokens d'invitation existants pour économiser les SMS.
 */
class ShortenInvitationTokensCommand extends Command
{
  /**
   * Signature de la commande Artisan.
   *
   * @var string
   */
  protected $signature = 'invitations:shorten-tokens
                          {--dry-run : Affiche les changements sans les enregistrer}
                          {--only-pending : Uniquement les invitations sans réponse RSVP}';

  /**
   * Description affichée dans la liste des commandes.
   *
   * @var string
   */
  protected $description = 'Remplace les tokens d\'invitation longs par des tokens courts (SMS).';

  /**
   * Exécute le raccourcissement des tokens.
   *
   * @param InvitationTokenGenerator $generator Générateur de tokens
   * @return int Code de sortie
   */
  public function handle(InvitationTokenGenerator $generator): int
  {
    $query = Invitation::query()->orderBy('created_at');

    if ($this->option('only-pending')) {
      $query->whereNull('responded_at');
    }

    $invitations = $query->get();
    $targetLength = $generator->length();
    $updated = 0;
    $skipped = 0;

    foreach ($invitations as $invitation) {
      if (strlen((string) $invitation->token) <= $targetLength) {
        $skipped++;
        continue;
      }

      $newToken = $generator->generateUnique();

      if ($this->option('dry-run')) {
        $this->line($invitation->full_name.' : '.$invitation->token.' → '.$newToken);
        $updated++;
        continue;
      }

      $invitation->update(['token' => $newToken]);
      $updated++;
    }

    $mode = $this->option('dry-run') ? ' (simulation)' : '';
    $this->info("Tokens raccourcis{$mode} : {$updated}. Ignorés (déjà courts) : {$skipped}.");

    if ($updated > 0 && ! $this->option('dry-run')) {
      $this->warn('Les anciens liens d\'invitation ne fonctionneront plus. Renvoyez les SMS si nécessaire.');
    }

    return self::SUCCESS;
  }
}
