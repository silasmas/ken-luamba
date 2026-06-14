<?php

namespace App\Filament\Widgets;

use App\Enums\InvitationDispatchChannel;
use App\Enums\InvitationDispatchStatus;
use App\Models\InvitationDispatchLog;
use App\Services\Invitations\InvitationDispatchService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Widget affichant le solde SMS Kecel et les statistiques d'envoi d'invitations.
 */
class InvitationMessagingStatsWidget extends StatsOverviewWidget
{
  protected static ?int $sort = 0;

  protected int|string|array $columnSpan = 'full';

  /**
   * Retourne les statistiques SMS et historique d'envoi.
   *
   * @return array<int, Stat> Cartes statistiques
   */
  protected function getStats(): array
  {
    $dispatchService = app(InvitationDispatchService::class);
    $stats = [];

    if ($dispatchService->usesKecelSms()) {
      $balance = $dispatchService->smsBalance();
      $balanceLabel = $balance['balance'] ?? null;
      $source = $balance['source'] ?? 'api';

      if ($balanceLabel === null && filled($balance['error'] ?? null)) {
        $balanceLabel = '—';
      }

      $description = match ($source) {
        'manual' => 'Solde manuel (API Kecel indisponible)',
        default => $balance['error'] ?? 'Crédits SMS restants',
      };

      $stats[] = Stat::make('Solde SMS Kecel', $balanceLabel ?? '—')
        ->description($description)
        ->descriptionIcon('heroicon-m-chat-bubble-left-right')
        ->color($balanceLabel !== null && $balanceLabel !== '—' ? 'info' : 'danger');
    }

    $emailSent = InvitationDispatchLog::query()
      ->where('channel', InvitationDispatchChannel::Email)
      ->where('status', InvitationDispatchStatus::Sent)
      ->count();

    $smsSent = InvitationDispatchLog::query()
      ->where('channel', InvitationDispatchChannel::Sms)
      ->where('status', InvitationDispatchStatus::Sent)
      ->count();

    $whatsappSent = InvitationDispatchLog::query()
      ->where('channel', InvitationDispatchChannel::Whatsapp)
      ->where('status', InvitationDispatchStatus::Sent)
      ->count();

    $stats[] = Stat::make('Emails envoyés', (string) $emailSent)
      ->description('Historique des envois email')
      ->descriptionIcon('heroicon-m-envelope')
      ->color('success');

    $stats[] = Stat::make('SMS envoyés', (string) $smsSent)
      ->description($dispatchService->usesKecelSms() ? 'Via API Kecel' : 'Mode manuel')
      ->descriptionIcon('heroicon-m-device-phone-mobile')
      ->color('warning');

    $stats[] = Stat::make('WhatsApp préparés', (string) $whatsappSent)
      ->description('Ouvertures WhatsApp journalisées')
      ->descriptionIcon('heroicon-m-chat-bubble-left-ellipsis')
      ->color('primary');

    return $stats;
  }
}
