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
   * @return array{created: int, skipped: int, errors: list<string>} Résultat de l'import
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
    $columnMap = null;
    $rowNumber = 0;

    foreach ($reader->getSheetIterator() as $sheet) {
      foreach ($sheet->getRowIterator() as $row) {
        $rowNumber++;
        $values = $this->rowToValues($row);

        if ($columnMap === null) {
          $columnMap = $this->resolveColumnMap($values);

          if ($columnMap === null) {
            $errors[] = 'Ligne 1 : en-têtes invalides. Utilisez le modèle Excel fourni.';
            break;
          }

          continue;
        }

        if ($this->isEmptyRow($values)) {
          continue;
        }

        $guest = $this->extractGuestData($values, $columnMap);

        if ($guest['full_name'] === '') {
          $errors[] = 'Ligne '.$rowNumber.' : le nom complet est obligatoire.';
          continue;
        }

        if ($this->invitationAlreadyExists($event, $guest)) {
          $skipped++;
          continue;
        }

        Invitation::query()->create([
          'event_id' => $event->id,
          'full_name' => $guest['full_name'],
          'email' => $guest['email'],
          'phone' => $guest['phone'],
          'organization' => $guest['organization'],
        ]);

        $created++;
      }

      break;
    }

    $reader->close();

    return [
      'created' => $created,
      'skipped' => $skipped,
      'errors' => $errors,
    ];
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
   * Vérifie si un invité similaire existe déjà pour l'événement.
   *
   * @param Event $event Événement cible
   * @param array{full_name: string, email: string|null, phone: string|null, organization: string|null} $guest Données invité
   * @return bool True si un doublon est détecté
   */
  private function invitationAlreadyExists(Event $event, array $guest): bool
  {
    $query = Invitation::query()->where('event_id', $event->id);

    if ($guest['email'] !== null) {
      return (clone $query)->where('email', $guest['email'])->exists();
    }

    if ($guest['phone'] !== null) {
      return (clone $query)->where('phone', $guest['phone'])->exists();
    }

    return (clone $query)
      ->where('full_name', $guest['full_name'])
      ->when(
        $guest['organization'] !== null,
        fn ($builder) => $builder->where('organization', $guest['organization']),
        fn ($builder) => $builder->whereNull('organization'),
      )
      ->exists();
  }
}
