<?php

namespace App\Services\Invitations;

use App\Models\Invitation;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

/**
 * Exporte la liste des réponses RSVP (présent / absent) en Excel et PDF.
 */
class InvitationRsvpExportService
{
  /**
   * Initialise le service d'export RSVP.
   *
   * @param InvitationAnalyticsService $analyticsService Agrégateur statistiques invitations
   */
  public function __construct(
    private readonly InvitationAnalyticsService $analyticsService,
  ) {}

  /**
   * Génère un fichier Excel des réponses RSVP filtrées.
   *
   * @param array<string, mixed>|null $filters Filtres de la page statistiques
   * @return string Chemin absolu du fichier généré
   */
  public function exportExcel(?array $filters): string
  {
    $eventId = $this->analyticsService->resolveEventId($filters);
    $invitations = $this->analyticsService->respondedInvitations($eventId);
    $includeEventColumn = $eventId === null;

    $directory = storage_path('app/exports');

    if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
      throw new RuntimeException('Impossible de créer le dossier d\'export.');
    }

    $filename = $this->buildFilename($eventId, 'xlsx');
    $path = $directory.DIRECTORY_SEPARATOR.$filename;

    $writer = new Writer();
    $writer->openToFile($path);
    $writer->getCurrentSheet()->setName('Réponses RSVP');

    $headers = [
      'Nom complet',
      'Email',
      'Téléphone',
      'Organisation',
      'Statut',
      'Date de réponse',
      'Commentaire invité',
    ];

    if ($includeEventColumn) {
      array_splice($headers, 4, 0, ['Événement']);
    }

    $writer->addRow(Row::fromValues($headers));

    foreach ($invitations as $invitation) {
      $writer->addRow(Row::fromValues($this->invitationRowValues($invitation, $includeEventColumn)));
    }

    $writer->close();

    return $path;
  }

  /**
   * Génère un fichier PDF des réponses RSVP filtrées.
   *
   * @param array<string, mixed>|null $filters Filtres de la page statistiques
   * @return string Chemin absolu du fichier généré
   */
  public function exportPdf(?array $filters): string
  {
    if (! class_exists(\Dompdf\Dompdf::class)) {
      throw new RuntimeException('Dompdf requis : composer require dompdf/dompdf');
    }

    $eventId = $this->analyticsService->resolveEventId($filters);
    $invitations = $this->analyticsService->respondedInvitations($eventId);
    $includeEventColumn = $eventId === null;
    $eventLabel = $this->analyticsService->eventLabel($eventId) ?? 'Tous les événements';

    $html = view('exports.invitation-rsvp-responses', [
      'title' => 'Réponses aux invitations',
      'eventLabel' => $eventLabel,
      'generatedAt' => now()->timezone(config('app.timezone'))->locale('fr')->isoFormat('dddd D MMMM YYYY [à] HH[h]mm'),
      'invitations' => $invitations,
      'includeEventColumn' => $includeEventColumn,
      'stats' => $this->analyticsService->overviewStats($eventId),
    ])->render();

    $directory = storage_path('app/exports');

    if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
      throw new RuntimeException('Impossible de créer le dossier d\'export.');
    }

    $path = $directory.DIRECTORY_SEPARATOR.$this->buildFilename($eventId, 'pdf');

    $dompdf = new \Dompdf\Dompdf([
      'isRemoteEnabled' => false,
      'isHtml5ParserEnabled' => true,
      'defaultFont' => 'DejaVu Sans',
    ]);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    file_put_contents($path, $dompdf->output());

    return $path;
  }

  /**
   * Construit le nom de fichier d'export.
   *
   * @param string|null $eventId Identifiant d'événement
   * @param string $extension Extension sans point
   * @return string Nom de fichier
   */
  private function buildFilename(?string $eventId, string $extension): string
  {
    $scope = $eventId === null
      ? 'tous-evenements'
      : Str::slug((string) $this->analyticsService->eventLabel($eventId), '-');

    return 'reponses-invitations-'.$scope.'-'.now()->format('Ymd-His').'.'.$extension;
  }

  /**
   * Transforme une invitation en ligne tabulaire pour Excel.
   *
   * @param Invitation $invitation Invitation exportée
   * @param bool $includeEventColumn Inclure la colonne événement
   * @return list<string> Valeurs de colonnes
   */
  private function invitationRowValues(Invitation $invitation, bool $includeEventColumn): array
  {
    $row = [
      (string) $invitation->full_name,
      (string) ($invitation->email ?? ''),
      (string) ($invitation->phone ?? ''),
      (string) ($invitation->organization ?? ''),
      (string) ($invitation->rsvp_status?->label() ?? ''),
      $invitation->responded_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '',
      (string) ($invitation->guest_message ?? ''),
    ];

    if ($includeEventColumn) {
      array_splice($row, 4, 0, [(string) ($invitation->event?->title ?? '')]);
    }

    return $row;
  }
}
