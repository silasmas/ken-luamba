<?php

namespace App\Filament\Widgets;

use App\Services\Mail\MailQuotaService;
use App\Support\OrderAdminFormatter;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Affiche le quota d'emails Hostinger estimé (fenêtre 24 h).
 */
class MailQuotaWidget extends StatsOverviewWidget
{
  // Visible aussi sur le dashboard ; sur Commandes via getHeaderWidgets().
  protected static ?int $sort = 0;

  protected int|string|array $columnSpan = 'full';

  protected ?string $heading = 'Quota emails (Hostinger)';

  protected ?string $description = 'Compteur des mails réellement envoyés par l\'app — plafond plan payant 1000 / 24 h';

  /**
   * Cartes used / remaining / limit.
   *
   * @return array<int, Stat>
   */
  protected function getStats(): array
  {
    $snapshot = app(MailQuotaService::class)->snapshot();
    $windowLabel = OrderAdminFormatter::formatLocalizedDateTime(
      now()->subDay(),
    );
    $mailer = (string) config('mail.default');
    $apiReady = app(\App\Services\Mail\HostingerMailApiClient::class)->isConfigured();

    return [
      Stat::make('Envoyés (24 h)', (string) $snapshot['used'])
        ->description(($snapshot['ready'] ?? true)
          ? 'Depuis '.$windowLabel
          : 'Lancez php artisan migrate (table mail_send_logs)')
        ->descriptionIcon('heroicon-m-paper-airplane')
        ->color($snapshot['color']),
      Stat::make('Restants estimés', (string) $snapshot['remaining'])
        ->description(sprintf('Sur %d max / 24 h (%.1f %% utilisés)', $snapshot['limit'], $snapshot['percent']))
        ->descriptionIcon('heroicon-m-inbox-stack')
        ->color($snapshot['color']),
      Stat::make('Plafond / canal', (string) $snapshot['limit'])
        ->description(sprintf(
          'Mailer: %s%s',
          $mailer,
          $apiReady ? ' · API Hostinger configurée' : ' · API Hostinger non configurée',
        ))
        ->descriptionIcon('heroicon-m-shield-check')
        ->color($mailer === 'hostinger' ? 'success' : 'info'),
    ];
  }
}
