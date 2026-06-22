<?php

namespace App\Services\Invitations;

use App\Models\Event;
use App\Models\Invitation;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

/**
 * Importe des invités depuis un fichier Excel et génère un modèle téléchargeable.
 */
class InvitationGuestImportService
{
  /**
   * En-têtes attendus dans le fichier modèle.
   *
   * @var list<string>
   */
  private const TEMPLATE_HEADERS = [
    'Nom complet',
    'Email',
    'Téléphone / WhatsApp',
    'Organisation',
  ];

  /**
   * Alias reconnus pour mapper les colonnes du fichier importé.
   *
   * @var array<string, string>
   */
  private const HEADER_ALIASES = [
    'nom complet' => 'full_name',
    'nom' => 'full_name',
    'full name' => 'full_name',
    'name' => 'full_name',
    'email' => 'email',
    'e-mail' => 'email',
    'telephone' => 'phone',
    'téléphone' => 'phone',
    'telephone / whatsapp' => 'phone',
    'téléphone / whatsapp' => 'phone',
    'whatsapp' => 'phone',
    'phone' => 'phone',
    'organisation' => 'organization',
    'organization' => 'organization',
    'org' => 'organization',
  ];

  /**
   * Génère un fichier Excel modèle pour l'import d'invités.
   *
   * @return string Chemin absolu du fichier généré
   */
  public function generateTemplate(): string
  {
    $directory = storage_path('app/exports');

    if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
      throw new RuntimeException('Impossible de créer le dossier d\'export.');
    }

    $path = $directory.DIRECTORY_SEPARATOR.'modele-invites.xlsx';

    $writer = new Writer();
    $writer->openToFile($path);
    $writer->getCurrentSheet()->setName('Invités');
    $writer->addRow(Row::fromValues(self::TEMPLATE_HEADERS));
    $writer->addRow(Row::fromValues([
      'Jean Mukendi',
      'jean@exemple.cd',
      '+243900000000',
      'Église Exemple',
    ]));
    $writer->close();

    return $path;
  }

  /**
   * Importe les invités d'un fichier Excel pour un événement.
   *
   * @param Event $event Événement cible
   * @param string $filePath Chemin absolu du fichier .xlsx
   * @return array{
   *   created: int,
   *   skipped: int,
   *   errors: list<string>,
   *   notRegistered: list<array{row: int, fullName: string, email: string|null, phone: string|null, reason: string}>
   * } Résultat de l'import
   */
  public function import(Event $event, string $filePath): array
  {
    if (! is_file($filePath)) {
      throw new RuntimeException('Fichier Excel introuvable.');
    }

    $reader = new Reader();
    $reader->open($filePath);

    $created = 0;
    $skipped = 0;
    $errors = [];
    $notRegistered = [];
    $columnMap = null;
    $rowNumber = 0;

    foreach ($reader->getSheetIterator() as $sheet) {
      foreach ($sheet->getRowIterator() as $row) {
        $rowNumber++;
        $values = $this->rowToValues($row);

        if ($columnMap === null) {
          $columnMap = $this->resolveColumnMap($values);

          if ($columnMap === null) {
            $reason = 'En-têtes invalides. Utilisez le modèle Excel fourni.';
            $errors[] = 'Ligne 1 : '.$reason;
            $notRegistered[] = $this->buildNotRegisteredEntry(1, '', null, null, $reason);
            break;
          }

          continue;
        }

        if ($this->isEmptyRow($values)) {
          continue;
        }

        $guest = $this->extractGuestData($values, $columnMap);

        if ($guest['full_name'] === '') {
          $reason = 'Le nom complet est obligatoire.';
          $errors[] = 'Ligne '.$rowNumber.' : '.$reason;
          $notRegistered[] = $this->buildNotRegisteredEntry(
            $rowNumber,
            '',
            $guest['email'],
            $guest['phone'],
            $reason,
          );
          continue;
        }

        $duplicateReason = $this->resolveDuplicateReason($event, $guest);

        if ($duplicateReason !== null) {
          $skipped++;
          $notRegistered[] = $this->buildNotRegisteredEntry(
            $rowNumber,
            $guest['full_name'],
            $guest['email'],
            $guest['phone'],
            $duplicateReason,
          );
          continue;
        }

        try {
          Invitation::query()->create([
            'event_id' => $event->id,
            'full_name' => $guest['full_name'],
            'email' => $guest['email'],
            'phone' => $guest['phone'],
            'organization' => $guest['organization'],
          ]);
        } catch (\Throwable $exception) {
          $reason = 'Erreur d\'enregistrement : '.$exception->getMessage();
          $errors[] = 'Ligne '.$rowNumber.' : '.$reason;
          $notRegistered[] = $this->buildNotRegisteredEntry(
            $rowNumber,
            $guest['full_name'],
            $guest['email'],
            $guest['phone'],
            $reason,
          );
          continue;
        }

        $created++;
      }

      break;
    }

    $reader->close();

    return [
      'created' => $created,
      'skipped' => $skipped,
      'errors' => $errors,
      'notRegistered' => $notRegistered,
    ];
  }

  /**
   * Formate le résumé d'un import pour affichage dans l'admin Filament.
   *
   * @param array{
   *   created: int,
   *   skipped: int,
   *   errors: list<string>,
   *   notRegistered: list<array{row: int, fullName: string, email: string|null, phone: string|null, reason: string}>
   * } $result Résultat de l'import
   * @return string Texte lisible pour notification
   */
  public function formatImportSummary(array $result): string
  {
    $lines = [
      $result['created'].' invité(s) ajouté(s).',
    ];

    if ($result['notRegistered'] === []) {
      return implode("\n", $lines);
    }

    $lines[] = count($result['notRegistered']).' invité(s) non enregistré(s) :';

    foreach ($result['notRegistered'] as $entry) {
      $lines[] = '• Ligne '.$entry['row'].' — '.$this->formatGuestLabel($entry).' : '.$entry['reason'];
    }

    return implode("\n", $lines);
  }

  /**
   * Construit une entrée détaillée pour un invité non enregistré.
   *
   * @param int $row Numéro de ligne Excel
   * @param string $fullName Nom complet
   * @param string|null $email Email
   * @param string|null $phone Téléphone
   * @param string $reason Motif du rejet
   * @return array{row: int, fullName: string, email: string|null, phone: string|null, reason: string} Détail
   */
  private function buildNotRegisteredEntry(
    int $row,
    string $fullName,
    ?string $email,
    ?string $phone,
    string $reason,
  ): array {
    return [
      'row' => $row,
      'fullName' => $fullName,
      'email' => $email,
      'phone' => $phone,
      'reason' => $reason,
    ];
  }

  /**
   * Formate le libellé d'un invité pour les messages d'import.
   *
   * @param array{fullName: string, email: string|null, phone: string|null} $entry Données invité
   * @return string Libellé compact
   */
  private function formatGuestLabel(array $entry): string
  {
    $name = filled($entry['fullName']) ? $entry['fullName'] : 'Invité sans nom';
    $details = array_values(array_filter([
      $entry['email'],
      $entry['phone'],
    ]));

    if ($details === []) {
      return $name;
    }

    return $name.' ('.implode(' — ', $details).')';
  }

  /**
   * Convertit une ligne OpenSpout en tableau de chaînes.
   *
   * @param Row $row Ligne lue
   * @return list<string|null> Valeurs normalisées
   */
  private function rowToValues(Row $row): array
  {
    $values = [];

    foreach ($row->getCells() as $cell) {
      $value = $cell->getValue();
      $values[] = is_string($value) ? trim($value) : (is_scalar($value) ? trim((string) $value) : null);
    }

    return $values;
  }

  /**
   * Détermine la correspondance entre colonnes Excel et champs du modèle.
   *
   * @param list<string|null> $headerRow Première ligne du fichier
   * @return array<string, int>|null Index des colonnes ou null si invalide
   */
  private function resolveColumnMap(array $headerRow): ?array
  {
    $map = [];

    foreach ($headerRow as $index => $header) {
      if (! is_string($header) || $header === '') {
        continue;
      }

      $normalized = mb_strtolower(trim($header));
      $field = self::HEADER_ALIASES[$normalized] ?? null;

      if ($field !== null && ! array_key_exists($field, $map)) {
        $map[$field] = $index;
      }
    }

    if (! array_key_exists('full_name', $map)) {
      return null;
    }

    return $map;
  }

  /**
   * Extrait les données invité depuis une ligne tabulaire.
   *
   * @param list<string|null> $values Valeurs de la ligne
   * @param array<string, int> $columnMap Correspondance champ → index
   * @return array{full_name: string, email: string|null, phone: string|null, organization: string|null} Données invité
   */
  private function extractGuestData(array $values, array $columnMap): array
  {
    return [
      'full_name' => $this->valueAt($values, $columnMap, 'full_name') ?? '',
      'email' => $this->nullableValueAt($values, $columnMap, 'email'),
      'phone' => $this->nullableValueAt($values, $columnMap, 'phone'),
      'organization' => $this->nullableValueAt($values, $columnMap, 'organization'),
    ];
  }

  /**
   * Lit une valeur textuelle à un index de colonne.
   *
   * @param list<string|null> $values Valeurs de la ligne
   * @param array<string, int> $columnMap Correspondance champ → index
   * @param string $field Nom du champ
   * @return string|null Valeur lue
   */
  private function valueAt(array $values, array $columnMap, string $field): ?string
  {
    if (! array_key_exists($field, $columnMap)) {
      return null;
    }

    $value = $values[$columnMap[$field]] ?? null;

    return is_string($value) ? trim($value) : null;
  }

  /**
   * Lit une valeur optionnelle (vide → null).
   *
   * @param list<string|null> $values Valeurs de la ligne
   * @param array<string, int> $columnMap Correspondance champ → index
   * @param string $field Nom du champ
   * @return string|null Valeur lue ou null
   */
  private function nullableValueAt(array $values, array $columnMap, string $field): ?string
  {
    $value = $this->valueAt($values, $columnMap, $field);

    return filled($value) ? $value : null;
  }

  /**
   * Indique si une ligne ne contient aucune donnée utile.
   *
   * @param list<string|null> $values Valeurs de la ligne
   * @return bool True si la ligne est vide
   */
  private function isEmptyRow(array $values): bool
  {
    foreach ($values as $value) {
      if (filled($value)) {
        return false;
      }
    }

    return true;
  }

  /**
   * Indique pourquoi un invité n'a pas pu être enregistré (doublon).
   *
   * @param Event $event Événement cible
   * @param array{full_name: string, email: string|null, phone: string|null, organization: string|null} $guest Données invité
   * @return string|null Motif du doublon ou null si absent
   */
  private function resolveDuplicateReason(Event $event, array $guest): ?string
  {
    $query = Invitation::query()->where('event_id', $event->id);

    if ($guest['email'] !== null && (clone $query)->where('email', $guest['email'])->exists()) {
      return 'Doublon : cet email est déjà enregistré pour cet événement.';
    }

    if ($guest['phone'] !== null && (clone $query)->where('phone', $guest['phone'])->exists()) {
      return 'Doublon : ce téléphone est déjà enregistré pour cet événement.';
    }

    $nameQuery = (clone $query)->where('full_name', $guest['full_name']);

    if ($guest['organization'] !== null) {
      $nameQuery->where('organization', $guest['organization']);
    } else {
      $nameQuery->whereNull('organization');
    }

    if ($nameQuery->exists()) {
      return 'Doublon : cet invité (nom et organisation) est déjà enregistré pour cet événement.';
    }

    return null;
  }
}
