<?php

namespace App\Filament\Resources\PricingPeriods\Pages;

use App\Filament\Resources\PricingPeriods\PricingPeriodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPricingPeriod extends EditRecord
{
    protected static string $resource = PricingPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
