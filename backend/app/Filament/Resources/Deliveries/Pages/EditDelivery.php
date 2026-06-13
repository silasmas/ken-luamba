<?php

namespace App\Filament\Resources\Deliveries\Pages;

use App\Enums\DeliveryStatus;
use App\Filament\Resources\Deliveries\DeliveryResource;
use App\Services\DeliveryService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditDelivery extends EditRecord
{
    protected static string $resource = DeliveryResource::class;

    /**
     * Actions d'en-tête dont l'assignation immédiate.
     *
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('assignNow')
                ->label('Assigner maintenant')
                ->icon(Heroicon::OutlinedClock)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Assigner la livraison maintenant ?')
                ->modalDescription('La date d\'assignation sera fixée à maintenant. La date de livraison sera renseignée plus tard par le livreur.')
                ->action(function (): void {
                    $courierId = $this->data['courier_id'] ?? $this->record->courier_id;

                    if ($courierId === null) {
                        Notification::make()
                            ->title('Sélectionnez un livreur avant d\'assigner.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->data['assigned_at'] = now();
                    $this->data['status'] = DeliveryStatus::Assigned->value;
                    $this->data['courier_id'] = $courierId;

                    $this->save();

                    $delivery = $this->record->fresh(['courier']);

                    if ($delivery->courier !== null) {
                        app(DeliveryService::class)->assignCourier($delivery, $delivery->courier);
                    }

                    Notification::make()
                        ->title('Livraison assignée')
                        ->body('Le livreur peut prendre la course dans son espace.')
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }

    /**
     * Synchronise l'assignation livreur avec le service métier après sauvegarde manuelle.
     */
    protected function afterSave(): void
    {
        $delivery = $this->record->fresh(['courier']);

        if (
            $delivery->courier_id !== null
            && $delivery->status === DeliveryStatus::Pending
            && $delivery->courier !== null
        ) {
            app(DeliveryService::class)->assignCourier($delivery, $delivery->courier);
        }
    }
}
