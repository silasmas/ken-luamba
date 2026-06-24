<?php

namespace App\Services\Invitations;

use App\Enums\InvitationRsvpStatus;
use App\Models\Event;
use App\Models\Invitation;
use Illuminate\Database\Eloquent\Builder;

/**
 * Agrège les statistiques d'invitations et de réponses RSVP pour l'admin.
 */
class InvitationAnalyticsService
{
  /**
   * Extrait l'identifiant d'événement depuis les filtres Filament.
   *
   * @param array<string, mixed>|null $filters Filtres de page
   * @return string|null Identifiant d'événement ou null (tous)
   */
  public function resolveEventId(?array $filters): ?string
  {
    $eventId = $filters['eventId'] ?? null;

    return filled($eventId) ? (string) $eventId : null;
  }

  /**
   * Retourne le libellé de l'événement filtré.
   *
   * @param string|null $eventId Identifiant d'événement
   * @return string|null Titre ou null si tous les événements
   */
  public function eventLabel(?string $eventId): ?string
  {
    if ($eventId === null) {
      return null;
    }

    return Event::query()->whereKey($eventId)->value('title');
  }

  /**
   * Statistiques globales pour les cartes du tableau de bord invitations.
   *
   * @param string|null $eventId Identifiant d'événement ou null
   * @return array{
   *   total: int,
   *   attending: int,
   *   notAttending: int,
   *   pending: int,
   *   emailSent: int,
   *   smsSent: int,
   *   whatsappSent: int,
   *   invitationsSent: int,
   *   responded: int,
   *   responseRate: float
   * } Indicateurs agrégés
   */
  public function overviewStats(?string $eventId): array
  {
    $baseQuery = $this->invitationQuery($eventId);

    $total = (clone $baseQuery)->count();
    $attending = (clone $baseQuery)->where('rsvp_status', InvitationRsvpStatus::Attending)->count();
    $notAttending = (clone $baseQuery)->where('rsvp_status', InvitationRsvpStatus::NotAttending)->count();
    $pending = (clone $baseQuery)->where('rsvp_status', InvitationRsvpStatus::Pending)->count();

    $emailSent = (clone $baseQuery)->whereNotNull('email_sent_at')->count();
    $smsSent = (clone $baseQuery)->whereNotNull('sms_sent_at')->count();
    $whatsappSent = (clone $baseQuery)->whereNotNull('whatsapp_sent_at')->count();

    $invitationsSent = (clone $baseQuery)
      ->where(function (Builder $query): void {
        $query->whereNotNull('email_sent_at')
          ->orWhereNotNull('sms_sent_at')
          ->orWhereNotNull('whatsapp_sent_at');
      })
      ->count();

    $responded = $attending + $notAttending;
    $responseRate = $total > 0 ? round(($responded / $total) * 100, 1) : 0.0;

    return [
      'total' => $total,
      'attending' => $attending,
      'notAttending' => $notAttending,
      'pending' => $pending,
      'emailSent' => $emailSent,
      'smsSent' => $smsSent,
      'whatsappSent' => $whatsappSent,
      'invitationsSent' => $invitationsSent,
      'responded' => $responded,
      'responseRate' => $responseRate,
    ];
  }

  /**
   * Répartition des réponses RSVP pour un graphique.
   *
   * @param string|null $eventId Identifiant d'événement ou null
   * @return array<string, int> Libellé statut => nombre
   */
  public function rsvpStatusCounts(?string $eventId): array
  {
    $stats = $this->overviewStats($eventId);

    return [
      InvitationRsvpStatus::Attending->label() => $stats['attending'],
      InvitationRsvpStatus::NotAttending->label() => $stats['notAttending'],
      InvitationRsvpStatus::Pending->label() => $stats['pending'],
    ];
  }

  /**
   * Nombre d'invitations envoyées par canal.
   *
   * @param string|null $eventId Identifiant d'événement ou null
   * @return array<string, int> Canal => nombre d'envois
   */
  public function sentByChannel(?string $eventId): array
  {
    $stats = $this->overviewStats($eventId);

    return [
      'Email' => $stats['emailSent'],
      'SMS' => $stats['smsSent'],
      'WhatsApp' => $stats['whatsappSent'],
    ];
  }

  /**
   * Statistiques RSVP par événement (top 12 ou événement filtré).
   *
   * @param string|null $eventId Identifiant d'événement ou null
   * @return array{
   *   labels: list<string>,
   *   attending: list<int>,
   *   notAttending: list<int>,
   *   pending: list<int>,
   *   sent: list<int>
   * } Séries pour graphique comparatif
   */
  public function statsByEvent(?string $eventId = null): array
  {
    $events = Event::query()
      ->when($eventId !== null, fn (Builder $builder): Builder => $builder->whereKey($eventId))
      ->whereHas('invitations')
      ->withCount([
        'invitations as invitations_count',
        'invitations as attending_count' => fn (Builder $inner): Builder => $inner
          ->where('rsvp_status', InvitationRsvpStatus::Attending),
        'invitations as not_attending_count' => fn (Builder $inner): Builder => $inner
          ->where('rsvp_status', InvitationRsvpStatus::NotAttending),
        'invitations as pending_count' => fn (Builder $inner): Builder => $inner
          ->where('rsvp_status', InvitationRsvpStatus::Pending),
        'invitations as sent_count' => fn (Builder $inner): Builder => $inner
          ->where(function (Builder $sentQuery): void {
            $sentQuery->whereNotNull('email_sent_at')
              ->orWhereNotNull('sms_sent_at')
              ->orWhereNotNull('whatsapp_sent_at');
          }),
      ])
      ->orderByDesc('starts_at')
      ->limit($eventId !== null ? 1 : 12)
      ->get();

    return [
      'labels' => $events->pluck('title')->map(fn (string $title): string => $this->truncateLabel($title))->all(),
      'attending' => $events->pluck('attending_count')->map(fn (mixed $value): int => (int) $value)->all(),
      'notAttending' => $events->pluck('not_attending_count')->map(fn (mixed $value): int => (int) $value)->all(),
      'pending' => $events->pluck('pending_count')->map(fn (mixed $value): int => (int) $value)->all(),
      'sent' => $events->pluck('sent_count')->map(fn (mixed $value): int => (int) $value)->all(),
    ];
  }

  /**
   * Évolution des réponses RSVP sur les 30 derniers jours.
   *
   * @param string|null $eventId Identifiant d'événement ou null
   * @return array{labels: list<string>, attending: list<int>, notAttending: list<int>} Séries journalières
   */
  public function responsesTrend(?string $eventId): array
  {
    $start = now()->subDays(29)->startOfDay();
    $end = now()->endOfDay();
    $labels = [];
    $attendingSeries = [];
    $notAttendingSeries = [];

    for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
      $labels[] = $day->locale('fr')->isoFormat('D MMM');
      $dayStart = $day->copy()->startOfDay();
      $dayEnd = $day->copy()->endOfDay();

      $attendingSeries[] = $this->invitationQuery($eventId)
        ->where('rsvp_status', InvitationRsvpStatus::Attending)
        ->whereBetween('responded_at', [$dayStart, $dayEnd])
        ->count();

      $notAttendingSeries[] = $this->invitationQuery($eventId)
        ->where('rsvp_status', InvitationRsvpStatus::NotAttending)
        ->whereBetween('responded_at', [$dayStart, $dayEnd])
        ->count();
    }

    return [
      'labels' => $labels,
      'attending' => $attendingSeries,
      'notAttending' => $notAttendingSeries,
    ];
  }

  /**
   * Construit la requête de base sur les invitations.
   *
   * @param string|null $eventId Identifiant d'événement ou null
   * @return Builder<Invitation> Requête Eloquent
   */
  private function invitationQuery(?string $eventId): Builder
  {
    $query = Invitation::query();

    if ($eventId !== null) {
      $query->where('event_id', $eventId);
    }

    return $query;
  }

  /**
   * Tronque un libellé d'événement pour l'axe d'un graphique.
   *
   * @param string $label Titre complet
   * @return string Libellé court
   */
  private function truncateLabel(string $label): string
  {
    return mb_strlen($label) > 28 ? mb_substr($label, 0, 25).'…' : $label;
  }
}
