<?php

namespace App\Filament\Support;

use App\Models\Order;
use App\Services\DeliveryService;
use App\Support\OrderAdminFormatter;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Actions Filament pour gérer la réception article par article.
 */
class OrderBooksReceivedAdminAction
{
  /**
   * Action modale de réception partielle ou complète.
   *
   * @return Action Action Filament configurée
   */
  public static function manageReceipt(): Action
  {
    return Action::make('manageBooksReceipt')
      ->label(fn (Order $record): string => $record->isDirectPayment()
        ? 'Gérer la récupération'
        : 'Gérer la réception')
      ->icon(Heroicon::OutlinedClipboardDocumentCheck)
      ->color('primary')
      ->visible(fn (Order $record): bool => self::canManageReceipt($record))
      ->fillForm(fn (Order $record): array => [
        'receivedItemIds' => OrderAdminFormatter::defaultReceivedItemIds($record),
      ])
      ->form(fn (Order $record): array => self::receiptFormSchema($record))
      ->action(function (Order $record, array $data): void {
        $service = app(DeliveryService::class);
        $receivedItemIds = $data['receivedItemIds'] ?? [];
        $service->syncPhysicalItemsReceiptByAdmin($record, is_array($receivedItemIds) ? $receivedItemIds : []);

        $record->refresh()->loadMissing('items');
        $counts = OrderAdminFormatter::booksReceivedCounts($record);
        $isDirect = $record->isDirectPayment();

        if (OrderAdminFormatter::isBooksReceived($record)) {
          Notification::make()
            ->title($isDirect ? 'Récupération complète' : 'Réception complète')
            ->body($isDirect
              ? 'Le client a récupéré tous les articles. Vente directe terminée.'
              : 'Tous les articles sont reçus. La commande est marquée comme terminée.')
            ->success()
            ->send();

          return;
        }

        $pending = OrderAdminFormatter::booksPendingSummary($record);

        Notification::make()
          ->title($isDirect ? 'Récupération partielle' : 'Réception partielle enregistrée')
          ->body($pending
            ? sprintf(
              '%d/%d article(s) %s. En attente : %s',
              $counts['received'],
              $counts['total'],
              $isDirect ? 'récupéré(s)' : 'reçu(s)',
              $pending,
            )
            : sprintf(
              '%d/%d article(s) %s.',
              $counts['received'],
              $counts['total'],
              $isDirect ? 'récupéré(s)' : 'reçu(s)',
            ))
          ->warning()
          ->send();
      });
  }

  /**
   * Indique si la réception peut être gérée pour cette commande.
   *
   * @param Order $record Commande cible
   * @return bool True si action disponible
   */
  private static function canManageReceipt(Order $record): bool
  {
    if ($record->isDigitalOnly()) {
      return false;
    }

    return ! in_array($record->status, [
      \App\Enums\OrderStatus::Cancelled,
      \App\Enums\OrderStatus::Refunded,
      \App\Enums\OrderStatus::PendingPayment,
    ], true);
  }

  /**
   * Schéma de formulaire de la modale de réception.
   *
   * @param Order $record Commande cible
   * @return list<CheckboxList|Placeholder> Champs Filament
   */
  private static function receiptFormSchema(Order $record): array
  {
    $record->loadMissing('items');
    $options = OrderAdminFormatter::physicalItems($record)
      ->mapWithKeys(fn ($item) => [$item->id => OrderAdminFormatter::physicalItemReceiptLabel($item)])
      ->all();

    $pending = OrderAdminFormatter::booksPendingSummary($record);

    $isDirect = $record->isDirectPayment();

    return [
      Placeholder::make('receipt_help')
        ->label('Instructions')
        ->content($isDirect
          ? 'Cochez les articles déjà récupérés par le client (remise en main propre). Quand tout est coché, la vente directe est marquée « A récupéré ».'
          : 'Cochez les articles déjà remis au client. Lorsque tous les articles physiques sont cochés, la commande sera marquée comme entièrement reçue.')
        ->columnSpanFull(),
      Placeholder::make('receipt_pending')
        ->label($isDirect ? 'Articles encore à récupérer' : 'Articles encore à remettre')
        ->content($pending ?? 'Aucun article en attente pour le moment.')
        ->visible(fn (): bool => $pending !== null)
        ->columnSpanFull(),
      CheckboxList::make('receivedItemIds')
        ->label($isDirect ? 'Articles récupérés' : 'Articles reçus')
        ->options($options)
        ->columns(1)
        ->bulkToggleable()
        ->columnSpanFull(),
    ];
  }
}
