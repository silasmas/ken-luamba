<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Services\OrderNotificationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

/**
 * Page d'édition d'une commande avec renvoi du mail d'achat.
 */
class EditOrder extends EditRecord
{
  protected static string $resource = OrderResource::class;

  /**
   * Actions d'en-tête de la fiche commande.
   *
   * @return array<int, Action>
   */
  protected function getHeaderActions(): array
  {
    return [
      Action::make('resendPurchaseEmail')
        ->label('Renvoyer mail achat')
        ->icon(Heroicon::OutlinedEnvelope)
        ->color('info')
        ->requiresConfirmation()
        ->modalHeading('Renvoyer le mail de confirmation')
        ->modalDescription(fn (): string => 'Un email de confirmation sera envoyé à '
          .($this->record->user?->email ?? 'l\'adresse du client').'.')
        ->visible(fn (): bool => $this->record->paid_at !== null && filled($this->record->user?->email))
        ->action(function (): void {
          $result = app(OrderNotificationService::class)->resendPaymentSuccessEmail($this->record);

          Notification::make()
            ->title($result['success'] ? 'Mail renvoyé' : 'Envoi impossible')
            ->body($result['message'])
            ->{$result['success'] ? 'success' : 'danger'}()
            ->send();

          if ($result['success']) {
            $this->refreshFormData(['payment_success_email_sent_at']);
            $this->record->refresh();
          }
        }),
      DeleteAction::make(),
    ];
  }
}
