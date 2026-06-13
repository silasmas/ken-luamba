<?php

namespace App\Filament\Resources\PickupPoints\Pages;

use App\Filament\Resources\PickupPoints\PickupPointResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPickupPoint extends EditRecord
{
    protected static string $resource = PickupPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
