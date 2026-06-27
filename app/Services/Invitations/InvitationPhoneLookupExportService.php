<?php

namespace App\Services\Invitations;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

/**
 * Exporte les résultats de correspondance téléphone → invité.
 */
class InvitationPhoneLookupExportService
{
  /**
   * Génère un fichier Excel à partir des lignes analysées.
   *
   * @param list<array<string, mixed>> $rows Lignes filtrées à exporter
   * @return string Chemin absolu du fichier
   */
  public function exportRows(array $rows): string
  {
    if ($rows === []) {
      throw new RuntimeException('Aucune ligne à exporter.');
    }

    $directory = storage_path('app/exports');

    if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
      throw new RuntimeException('Impossible de créer le dossier d\'export.');
    }

    $path = $directory.DIRECTORY_SEPARATOR.'correspondance-telephones-'.now()->format('Ymd-His').'.xlsx';

    $writer = new Writer();
    $writer->openToFile($path);
    $writer->getCurrentSheet()->setName('Correspondances');

    $writer->addRow(Row::fromValues([
      'Numéro saisi',
      'Numéro normalisé',
      'Correspondance',
      'Statut RSVP',
      'Nom invité',
      'Email',
      'Événement',
      'Lien invitation',
    ]));

    foreach ($rows as $row) {
      $writer->addRow(Row::fromValues([
        (string) ($row['input'] ?? ''),
        (string) ($row['normalized'] ?? ''),
        (string) ($row['statusLabel'] ?? ''),
        (string) ($row['rsvpStatusLabel'] ?? ''),
        (string) ($row['fullName'] ?? ''),
        (string) ($row['email'] ?? ''),
        (string) ($row['eventTitle'] ?? ''),
        (string) ($row['invitationLink'] ?? ''),
      ]));
    }

    $writer->close();

    return $path;
  }
}
