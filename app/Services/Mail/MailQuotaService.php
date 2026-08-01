<?php

namespace App\Services\Mail;

use App\Models\MailSendLog;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Schema;

/**
 * Estime la consommation du quota d'envoi Hostinger (fenêtre glissante 24 h).
 */
class MailQuotaService
{
  /**
   * Plafond quotidien configuré (plan Hostinger Business ≈ 1000).
   *
   * @return int Nombre max d'envois / 24 h
   */
  public function dailyLimit(): int
  {
    return max(1, (int) config('mail.daily_limit', 1000));
  }

  /**
   * Début de la fenêtre glissante (24 dernières heures).
   *
   * @return CarbonInterface Instant de début
   */
  public function windowStart(): CarbonInterface
  {
    return now()->subDay();
  }

  /**
   * Indique si la table de journalisation existe (après migrate).
   *
   * @return bool True si le compteur peut lire la BDD
   */
  public function isReady(): bool
  {
    return Schema::hasTable('mail_send_logs');
  }

  /**
   * Nombre d'emails réellement envoyés par l'app sur 24 h.
   *
   * @return int Envois journalés (0 si table absente)
   */
  public function usedInRolling24h(): int
  {
    if (! $this->isReady()) {
      return 0;
    }

    return MailSendLog::query()
      ->where('created_at', '>=', $this->windowStart())
      ->count();
  }

  /**
   * Nombre d'envois encore disponibles avant le plafond estimé.
   *
   * @return int Restants (min 0)
   */
  public function remainingInRolling24h(): int
  {
    return max(0, $this->dailyLimit() - $this->usedInRolling24h());
  }

  /**
   * Pourcentage d'utilisation du plafond.
   *
   * @return float Pourcentage 0–100
   */
  public function usagePercent(): float
  {
    return round(($this->usedInRolling24h() / $this->dailyLimit()) * 100, 1);
  }

  /**
   * Indique s'il reste de la marge pour envoyer encore.
   *
   * @param int $needed Nombre d'envois prévus
   * @return bool True si le quota estimé le permet
   */
  public function canSend(int $needed = 1): bool
  {
    // Sans table (migration pas encore jouée), on ne bloque pas l'envoi.
    if (! $this->isReady()) {
      return true;
    }

    return $this->remainingInRolling24h() >= max(1, $needed);
  }

  /**
   * Snapshot pour l'UI admin.
   *
   * @return array{
   *   limit: int,
   *   used: int,
   *   remaining: int,
   *   percent: float,
   *   window_start: string,
   *   can_send: bool,
   *   color: string,
   *   label: string
   * }
   */
  public function snapshot(): array
  {
    $limit = $this->dailyLimit();
    $ready = $this->isReady();
    $used = $this->usedInRolling24h();
    $remaining = max(0, $limit - $used);
    $percent = $ready ? round(($used / $limit) * 100, 1) : 0.0;

    $color = match (true) {
      ! $ready => 'danger',
      $remaining <= 0 => 'danger',
      $remaining <= 50 => 'danger',
      $remaining <= 200 => 'warning',
      default => 'success',
    };

    return [
      'limit' => $limit,
      'used' => $used,
      'remaining' => $remaining,
      'percent' => $percent,
      'window_start' => $this->windowStart()->toIso8601String(),
      'can_send' => ! $ready || $remaining > 0,
      'ready' => $ready,
      'color' => $color,
      'label' => $ready
        ? sprintf('%d restants / %d (24 h)', $remaining, $limit)
        : 'Migration requise (mail_send_logs)',
    ];
  }
}
