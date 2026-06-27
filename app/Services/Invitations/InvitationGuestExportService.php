<?php

namespace App\Services\Invitations;

use App\Models\Invitation;
use App\Support\ContactLinkHelper;
use Illuminate\Database\Eloquent\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

/**
 * Exporte une sélection d'invitations vers Excel avec token et lien court.
 */
class InvitationGuestExportService
{
  /**
   * Initialise le service avec le générateur de liens.
   *
   * @param InvitationLinkService $linkService Service de liens publics
   */
  public function __construct(
    private readonly InvitationLinkService $linkService,
  ) {}

  /**
   * Génère un fichier Excel pour les invitations sélectionnées.
   *
   * @param Collection<int, Invitation> $invitations Invitations à exporter
   * @return string Chemin absolu du fichier généré
   */
  public function exportSelection(Collection $invitations): string
  {
    if ($invitations->isEmpty()) {
      throw new RuntimeException('Sélectionnez au moins un invité à exporter.');
    }

    $invitations->loadMissing('event:id,title');

    $directory = storage_path('app/exports');

    if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
      throw new RuntimeException('Impossible de créer le dossier d\'export.');
    }

    $filename = 'invites-selection-'.now()->format('Ymd-His').'.xlsx';
    $path = $directory.DIRECTORY_SEPARATOR.$filename;

    $writer = new Writer();
    $writer->openToFile($path);
    $writer->getCurrentSheet()->setName('Invités');

    $writer->addRow(Row::fromValues([
      'Nom complet',
      'Email',
      'Téléphone / WhatsApp',
      'Type d\'invité',
      'Événement',
      'Token',
      'Lien invitation',
      'Statut RSVP',
      'Email envoyé le',
      'SMS envoyé le',
      'WhatsApp envoyé le',
      'Commentaire invité',
    ]));

    foreach ($invitations as $invitation) {
      if (! $invitation instanceof Invitation) {
        continue;
      }

      $writer->addRow(Row::fromValues($this->invitationRowValues($invitation)));
    }

    $writer->close();

    return $path;
  }

  /**
   * Transforme une invitation en ligne Excel.
   *
   * @param Invitation $invitation Invitation exportée
   * @return list<string> Valeurs des colonnes
   */
  private function invitationRowValues(Invitation $invitation): array
  {
    $link = $this->linkService->publicUrl($invitation);
    $timezone = config('app.timezone');

    return [
      (string) $invitation->full_name,
      (string) ($invitation->email ?? ''),
      ContactLinkHelper::digits($invitation->phone),
      (string) ($invitation->organization ?? ''),
      (string) ($invitation->event?->title ?? ''),
      (string) $invitation->token,
      $link,
      (string) ($invitation->rsvp_status?->label() ?? ''),
      $invitation->email_sent_at?->timezone($timezone)->format('d/m/Y H:i') ?? '',
      $invitation->sms_sent_at?->timezone($timezone)->format('d/m/Y H:i') ?? '',
      $invitation->whatsapp_sent_at?->timezone($timezone)->format('d/m/Y H:i') ?? '',
      (string) ($invitation->guest_message ?? ''),
    ];
  }
}
