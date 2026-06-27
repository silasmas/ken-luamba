<?php

namespace App\Services\Invitations;

use App\Models\Event;
use App\Models\Invitation;
use App\Support\ContactLinkHelper;
use Illuminate\Support\Collection;

/**
 * Associe une liste de numéros saisis aux invités enregistrés.
 */
class InvitationPhoneLookupService
{
  /**
   * Extrait les numéros d'un texte collé (lignes, virgules, point-virgules).
   *
   * @param string $raw Texte brut saisi
   * @return list<string> Numéros bruts non vides
   */
  public function parsePhoneList(string $raw): array
  {
    $parts = preg_split('/[\r\n,;]+/', $raw) ?: [];
    $phones = [];

    foreach ($parts as $part) {
      $value = trim((string) $part);

      if ($value !== '') {
        $phones[] = $value;
      }
    }

    return $phones;
  }

  /**
   * Recherche les invités correspondant aux numéros fournis.
   *
   * @param list<string> $phones Numéros saisis
   * @param string|null $eventId Identifiant d'événement optionnel
   * @return array{
   *   rows: list<array{
   *     input: string,
   *     normalized: string,
   *     matched: bool,
   *     fullName: string|null,
   *     email: string|null,
   *     eventTitle: string|null,
   *     statusLabel: string
   *   }>,
   *   stats: array{total: int, matched: int, unmatched: int}
   * }
   */
  public function lookup(array $phones, ?string $eventId = null): array
  {
    $index = $this->buildInvitationIndex($eventId);
    $rows = [];
    $matchedCount = 0;

    foreach ($phones as $phone) {
      $normalized = ContactLinkHelper::digits($phone);
      $invitation = $normalized !== '' ? $this->findInvitation($index, $normalized) : null;
      $isMatched = $invitation !== null;

      if ($isMatched) {
        $matchedCount++;
      }

      $rows[] = [
        'input' => $phone,
        'normalized' => $normalized,
        'matched' => $isMatched,
        'fullName' => $invitation?->full_name,
        'email' => $invitation?->email,
        'eventTitle' => $invitation?->event?->title,
        'statusLabel' => $isMatched ? 'Correspondance trouvée' : 'Nom non trouvé',
      ];
    }

    $total = count($rows);

    return [
      'rows' => $rows,
      'stats' => [
        'total' => $total,
        'matched' => $matchedCount,
        'unmatched' => $total - $matchedCount,
      ],
    ];
  }

  /**
   * Filtre les lignes analysées selon le statut de correspondance.
   *
   * @param list<array<string, mixed>> $rows Lignes analysées
   * @param string $filter all|matched|unmatched
   * @return list<array<string, mixed>> Lignes filtrées
   */
  public function filterRows(array $rows, string $filter): array
  {
    return match ($filter) {
      'matched' => array_values(array_filter($rows, fn (array $row): bool => (bool) $row['matched'])),
      'unmatched' => array_values(array_filter($rows, fn (array $row): bool => ! (bool) $row['matched'])),
      default => $rows,
    };
  }

  /**
   * Options événements pour le formulaire admin.
   *
   * @return array<string, string> id => titre
   */
  public function eventOptions(): array
  {
    return Event::query()
      ->orderByDesc('starts_at')
      ->pluck('title', 'id')
      ->all();
  }

  /**
   * Indexe les invitations par numéro normalisé.
   *
   * @param string|null $eventId Filtre événement
   * @return array<string, Invitation> Index digits => invitation
   */
  private function buildInvitationIndex(?string $eventId): array
  {
    $query = Invitation::query()
      ->with('event:id,title')
      ->whereNotNull('phone')
      ->where('phone', '!=', '');

    if ($eventId !== null && $eventId !== '') {
      $query->where('event_id', $eventId);
    }

    /** @var Collection<int, Invitation> $invitations */
    $invitations = $query->get();
    $index = [];

    foreach ($invitations as $invitation) {
      $digits = ContactLinkHelper::digits($invitation->phone);

      if ($digits === '') {
        continue;
      }

      $this->registerInvitationKey($index, $digits, $invitation);

      if (strlen($digits) > 9) {
        $this->registerInvitationKey($index, substr($digits, -9), $invitation);
      }
    }

    return $index;
  }

  /**
   * Enregistre une invitation dans l'index sans écraser une entrée existante.
   *
   * @param array<string, Invitation> $index Index courant
   * @param string $key Clé de recherche
   * @param Invitation $invitation Invitation trouvée
   * @return void
   */
  private function registerInvitationKey(array &$index, string $key, Invitation $invitation): void
  {
    if (! array_key_exists($key, $index)) {
      $index[$key] = $invitation;
    }
  }

  /**
   * Trouve une invitation à partir d'un numéro normalisé.
   *
   * @param array<string, Invitation> $index Index des invitations
   * @param string $normalized Numéro chiffres uniquement
   * @return Invitation|null Invitation correspondante
   */
  private function findInvitation(array $index, string $normalized): ?Invitation
  {
    if (isset($index[$normalized])) {
      return $index[$normalized];
    }

    if (strlen($normalized) > 9 && isset($index[substr($normalized, -9)])) {
      return $index[substr($normalized, -9)];
    }

    if (strlen($normalized) === 9 && isset($index['243'.$normalized])) {
      return $index['243'.$normalized];
    }

    return null;
  }
}
