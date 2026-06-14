<?php

namespace App\Services\Invitations;

use App\Enums\InvitationDispatchChannel;
use App\Enums\InvitationDispatchStatus;
use App\Models\Invitation;
use App\Models\InvitationDispatchLog;
use Illuminate\Support\Facades\Auth;

/**
 * Enregistre l'historique des envois d'invitations.
 */
class InvitationDispatchLogger
{
  /**
   * Journalise un envoi réussi ou échoué.
   *
   * @param Invitation $invitation Invitation cible
   * @param InvitationDispatchChannel $channel Canal utilisé
   * @param string $recipient Email ou téléphone
   * @param string $messageBody Contenu envoyé
   * @param InvitationDispatchStatus $status Statut de l'envoi
   * @param string|null $messageTemplateId Identifiant du modèle utilisé
   * @param string|null $providerResponse Réponse du fournisseur (Kecel, etc.)
   * @return InvitationDispatchLog Entrée créée
   */
  public function log(
    Invitation $invitation,
    InvitationDispatchChannel $channel,
    string $recipient,
    string $messageBody,
    InvitationDispatchStatus $status,
    ?string $messageTemplateId = null,
    ?string $providerResponse = null,
  ): InvitationDispatchLog {
    $invitation->loadMissing('event');

    return InvitationDispatchLog::query()->create([
      'invitation_id' => $invitation->id,
      'event_id' => $invitation->event_id,
      'sent_by' => Auth::id(),
      'channel' => $channel,
      'recipient' => $recipient,
      'recipient_name' => $invitation->full_name,
      'message_template_id' => $messageTemplateId,
      'message_body' => $messageBody,
      'status' => $status,
      'provider_response' => $providerResponse,
      'sent_at' => now(),
    ]);
  }
}
